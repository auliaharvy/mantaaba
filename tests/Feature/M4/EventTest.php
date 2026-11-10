<?php

namespace Tests\Feature\M4;

use App\Models\Batch;
use App\Models\Event;
use App\Models\Member;
use App\Models\Participant;
use App\Models\Skema;
use App\Models\User;
use App\Notifications\EventTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Agenda & kehadiran (PRD 9.8, AC M4). */
class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function guest(array $override = []): array
    {
        return array_merge(['guest_name' => 'Tamu Umum', 'guest_phone' => '0812 7777 8888', 'guest_email' => 'tamu@contoh.id'], $override);
    }

    public function test_public_registration_without_account_with_ticket_and_quota()
    {
        Notification::fake();
        $event = Event::factory()->create(['title' => 'Open House', 'quota' => 2]);
        $this->get("/agenda/{$event->slug}")->assertOk()->assertSee('Daftar Gratis')->assertSee('No. WhatsApp');

        $response = $this->post("/agenda/{$event->slug}/daftar", $this->guest());
        $participant = Participant::where('event_id', $event->id)->firstOrFail();
        $this->assertNull($participant->user_id);
        $response->assertRedirectContains("/agenda/{$event->slug}/tiket/{$participant->ticket_code}");
        $this->get($response->headers->get('Location'))->assertOk()->assertSee($participant->ticket_code)->assertSee('<svg', false);
        $this->get("/agenda/{$event->slug}/tiket/{$participant->ticket_code}")->assertForbidden(); // tanpa tanda tangan
        Notification::assertSentOnDemand(EventTicket::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === 'tamu@contoh.id');

        $this->post("/agenda/{$event->slug}/daftar", $this->guest())->assertSessionHasErrors(['event' => 'Anda sudah terdaftar di agenda ini.']);
        $this->post("/agenda/{$event->slug}/daftar", ['guest_name' => '', 'guest_phone' => '12'])->assertSessionHasErrors(['guest_name', 'guest_phone']);
        $this->actingAs($this->user('anggota@mail.com'))->post("/agenda/{$event->slug}/daftar")->assertRedirect();
        auth()->logout();
        // AC: pendaftar ke-(kuota+1) → "Kuota penuh"
        $this->post("/agenda/{$event->slug}/daftar", $this->guest(['guest_phone' => '0813 1111 2222']))->assertSessionHasErrors(['event' => 'Kuota penuh.']);
        $this->assertSame(2, $event->seatsTaken());
    }

    public function test_registration_window_and_internal_events_have_no_public_form()
    {
        $closed = Event::factory()->create(['registration_closes_at' => now()->subHour()]);
        $this->post("/agenda/{$closed->slug}/daftar", $this->guest())->assertSessionHasErrors('event');
        $this->get("/agenda/{$closed->slug}")->assertSee('Pendaftaran sedang tidak dibuka');

        $internal = Event::factory()->create(['audience' => 'anggota']);
        $this->get("/agenda/{$internal->slug}")->assertSee('Agenda internal')->assertDontSee('Daftar Gratis');
        $this->post("/agenda/{$internal->slug}/daftar", $this->guest())->assertSessionHasErrors('event');

        $draft = Event::factory()->create(['status' => 'draft']);
        $this->get("/agenda/{$draft->slug}")->assertNotFound();
    }

    public function test_internal_events_get_automatic_participants()
    {
        $batch = Batch::where('status', 'running')->firstOrFail();
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.store'), [
            'title' => 'Kelas SIA: Jurnal Umum', 'type' => 'kelas', 'skema_id' => Skema::where('code', 'SIA')->value('id'), 'mode' => 'offline',
            'starts_at' => now()->addDay()->format('Y-m-d\TH:i'), 'ends_at' => now()->addDay()->addHours(2)->format('Y-m-d\TH:i'),
            'audience' => 'angkatan', 'batch_id' => $batch->id, 'status' => 'open',
        ])->assertSessionHasNoErrors();
        $event = Event::where('title', 'Kelas SIA: Jurnal Umum')->firstOrFail();
        $withAccount = $batch->members()->whereNotNull('user_id')->count();
        $this->assertSame($withAccount, $event->participants()->count());
        $this->actingAs($this->user('anggota@mail.com'))->get(route('my-training.index'))->assertSee('Kelas SIA: Jurnal Umum');

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.store'), ['title' => 'X', 'type' => 'kelas', 'mode' => 'offline',
            'starts_at' => now()->format('Y-m-d\TH:i'), 'ends_at' => now()->addHour()->format('Y-m-d\TH:i'), 'audience' => 'angkatan', 'status' => 'open'])->assertSessionHasErrors('batch_id');
    }

    public function test_check_in_by_ticket_or_member_card_and_bulk_attendance()
    {
        $event = Event::factory()->create(['audience' => 'anggota']);
        $event->syncParticipants();
        $pengurus = $this->user('pengurus@mail.com');
        $demo = $this->user('anggota@mail.com');
        $memberNumber = $demo->member->member_number;

        $this->actingAs($pengurus)->postJson(route('event.check-in.store', $event), ['code' => url("/dashboard/anggota/verifikasi/{$memberNumber}")])
            ->assertOk()->assertJson(['ok' => true, 'message' => 'Anggota Demo berhasil check-in.']);
        $this->actingAs($pengurus)->postJson(route('event.check-in.store', $event), ['code' => $memberNumber])->assertJson(['message' => 'Anggota Demo sudah check-in sebelumnya.']);
        $this->actingAs($pengurus)->postJson(route('event.check-in.store', $event), ['code' => 'SALAHKODE'])->assertNotFound();

        $guestEvent = Event::factory()->create();
        $guest = Participant::factory()->create(['event_id' => $guestEvent->id]);
        $this->actingAs($pengurus)->postJson(route('event.check-in.store', $guestEvent), ['code' => strtolower($guest->ticket_code)])->assertJson(['ok' => true]);
        $this->assertSame('hadir', $guest->fresh()->status);

        $ids = $event->participants()->pluck('id')->all();
        $this->actingAs($pengurus)->patch(route('event.attendance', $event), ['ids' => $ids, 'present' => 1])->assertSessionHas('success');
        $this->assertSame(count($ids), $event->participants()->where('status', 'hadir')->count());
        $this->actingAs($this->user('anggota@mail.com'))->postJson(route('event.check-in.store', $event), ['code' => $memberNumber])->assertForbidden();
    }

    public function test_class_attendance_appears_in_assessment_summary()
    {
        $fnd = Skema::where('code', 'FND')->firstOrFail();
        $batch = Batch::where('status', 'running')->firstOrFail();
        // seeder: 1 kelas FND selesai, siswa berakun (Anggota Demo) hadir; Fajar diberi akun & tercatat peserta tetapi tidak hadir
        $fajar = Member::where('name', 'Fajar Nugraha')->firstOrFail();
        $fajar->update(['user_id' => User::factory()->create()->assignRole('anggota')->id]);
        Event::where('type', 'kelas')->firstOrFail()->participants()->create(['user_id' => $fajar->user_id]);
        $html = $this->actingAs($this->user('pengurus@mail.com'))->get(route('assessment.index', ['batch' => $batch->id, 'skema' => $fnd->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<td>1/1</td>#', $html);
        $this->assertMatchesRegularExpression('#<td>0/1</td>#', $html);
    }

    public function test_sedulur_selawase_report_lists_absent_alumni()
    {
        $alumni = Member::where('name', 'Rizal Maulana')->firstOrFail();
        $alumniUser = User::factory()->create()->assignRole('anggota');
        $alumni->update(['user_id' => $alumniUser->id, 'category' => 'alumni', 'phase' => 'alumni', 'phone' => '0812 0000 1111']);
        $present = Member::where('name', 'Fajar Nugraha')->firstOrFail();
        $presentUser = User::factory()->create()->assignRole('anggota');
        $present->update(['user_id' => $presentUser->id, 'phase' => 'magang']);
        $event = Event::factory()->create(['type' => 'sedulur_selawase', 'audience' => 'anggota', 'status' => 'done', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()->addHours(2)]);
        $event->participants()->create(['user_id' => $presentUser->id])->markPresent();
        $event->participants()->create(['user_id' => $alumniUser->id]);
        $old = Event::factory()->create(['type' => 'sedulur_selawase', 'status' => 'done', 'starts_at' => now()->subMonths(5), 'ends_at' => now()->subMonths(5)->addHour()]);
        $old->participants()->create(['user_id' => $alumniUser->id])->markPresent();

        $this->actingAs($this->user('pengurus@mail.com'))->get(route('event.selawase'))->assertOk()
            ->assertSee('Rizal Maulana')->assertDontSee('Fajar Nugraha')->assertSee('https://wa.me/6281200001111', false)->assertSee(now()->subMonths(5)->translatedFormat('j M Y'));
        $csv = $this->actingAs($this->user('pengurus@mail.com'))->get(route('event.selawase', ['export' => 1]))->streamedContent();
        $this->assertStringContainsString('Rizal Maulana', $csv);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('event.selawase'))->assertForbidden();
    }

    public function test_calendars_export_and_announcement()
    {
        Notification::fake();
        $event = Event::factory()->create(['title' => 'Kajian Kalender', 'starts_at' => now()->startOfMonth()->addDays(10)->setTime(19, 0), 'ends_at' => now()->startOfMonth()->addDays(10)->setTime(21, 0)]);
        Participant::factory()->create(['event_id' => $event->id, 'guest_email' => 'a@contoh.id']);
        $this->actingAs($this->user('pengurus@mail.com'))->getJson(route('event.calendar', ['start' => now()->startOfMonth()->toDateString(), 'end' => now()->endOfMonth()->toDateString()]))
            ->assertOk()->assertJsonFragment(['title' => 'Kajian Kalender']);
        $this->get('/agenda?tab=kalender')->assertOk()->assertSee('Kajian Kalender');

        $csv = $this->actingAs($this->user('pengurus@mail.com'))->get(route('event.participants.export', $event))->streamedContent();
        $this->assertStringContainsString('tamu', $csv);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.announce', $event), ['subject' => 'Info', 'message' => 'Bawa Al-Quran'])->assertSessionHas('success', 'Pengumuman dikirim ke 1 email peserta.');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.duplicate', $event))->assertRedirect();
        $this->assertSame('draft', Event::where('title', 'Kajian Kalender (salinan)')->value('status'));
    }
}
