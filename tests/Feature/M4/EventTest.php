<?php

namespace Tests\Feature\M4;

use App\Jobs\GenerateCertificatePdf;
use App\Jobs\IssueEventCertificates;
use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\CertificateIssued;
use App\Notifications\EventAnnouncement;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Agenda, pendaftaran, kehadiran, sertifikat (kode lama; disederhanakan di M3/M4, PRD 9.7–9.8). */
class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Notification::fake();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create($attributes);
    }

    /** Pengguna login tanpa role anggota (pengganti akun "umum" lama). */
    private function umum(): User
    {
        return User::factory()->create();
    }

    private function register(User $user, Event $event)
    {
        return $this->actingAs($user)->post(route('site.training.register', $event->slug));
    }

    // ---------------- Pendaftaran (8.9 alur 2–3)

    public function test_free_event_is_verified_automatically_and_quota_plus_one_is_rejected()
    {
        $event = $this->event(['quota' => 2]);
        [$a, $b, $c] = [$this->umum(), $this->umum(), $this->umum()];

        $this->register($a, $event)->assertRedirect();
        $this->register($b, $event)->assertRedirect();
        $this->register($c, $event)->assertSessionHasErrors(['event' => 'Kuota penuh.']);

        $this->assertSame('verified', $a->participants()->first()->status);
        $this->assertSame(2, $event->participants()->count());
        Notification::assertSentTo($a, RegistrationStatusChanged::class);
        $this->get("/agenda/{$event->slug}")->assertSee('Kuota penuh');
    }

    public function test_umum_cannot_register_for_members_only_event()
    {
        $event = $this->event(['audience' => 'anggota']);

        $this->register($this->umum(), $event)->assertSessionHasErrors(['event' => 'Agenda ini khusus anggota Rumah Mantaaba Sigaling.']);
        $this->register($this->user('anggota@mail.com'), $event)->assertRedirect();
        $this->assertSame(1, $event->participants()->count());
    }

    public function test_registration_window_and_duplicate_signup_are_rejected()
    {
        $closed = $this->event(['registration_closes_at' => now()->subDay()]);
        $this->register($this->umum(), $closed)->assertSessionHasErrors('event');

        $event = $this->event();
        $user = $this->umum();
        $this->register($user, $event)->assertRedirect();
        $this->register($user, $event)->assertSessionHasErrors(['event' => 'Anda sudah terdaftar di event ini.']);
    }

    public function test_meeting_url_hidden_for_pending_and_shown_after_verification_with_proof_flow()
    {
        Storage::fake('local');
        $event = $this->event(['fee_public' => 300000, 'mode' => 'online', 'meeting_url' => 'https://zoom.us/j/rahasia123']);
        $user = $this->umum();
        $this->register($user, $event);
        $participant = $user->participants()->firstOrFail();

        $this->actingAs($user)->get(route('my-training.show', $participant))->assertOk()->assertDontSee('zoom.us/j/rahasia123')->assertDontSee($participant->ticket_code);
        $this->get("/agenda/{$event->slug}")->assertDontSee('zoom.us/j/rahasia123');

        $this->actingAs($user)->post(route('my-training.proof', $participant), ['payment_proof' => UploadedFile::fake()->image('tf.jpg')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($participant->fresh()->payment_proof);

        // bukti bayar: pemilik & pengelola event saja
        $this->actingAs($user)->get(route('participant.proof', $participant))->assertOk();
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('participant.proof', $participant))->assertOk();
        $this->actingAs($this->umum())->get(route('participant.proof', $participant))->assertForbidden();
        $this->actingAs(User::factory()->create()->assignRole('pengurus')->givePermissionTo('news-view'))->get(route('participant.proof', $participant))->assertForbidden();

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('participant.status', $participant), ['status' => 'rejected'])->assertSessionHasErrors('notes');
        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('participant.status', $participant), ['status' => 'verified'])->assertSessionHas('success');
        Notification::assertSentTo($user, RegistrationStatusChanged::class, fn ($n) => $n->participant->status === 'verified');
        $this->assertTrue(ActivityLog::where('subject_type', Participant::class)->where('subject_id', $participant->id)->where('action', 'status_changed')->exists());

        $this->actingAs($user)->get(route('my-training.show', $participant))->assertSee('zoom.us/j/rahasia123')->assertSee($participant->ticket_code);
    }

    // ---------------- Kehadiran (8.9 alur 6)

    public function test_check_in_by_ticket_code_and_bulk_attendance()
    {
        $event = $this->event();
        $admin = $this->user('pengurus@mail.com');
        $verified = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id]);
        $pending = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id, 'status' => 'pending']);

        $this->actingAs($admin)->postJson(route('event.check-in.store', $event), ['code' => strtolower($verified->ticket_code)])->assertOk()->assertJson(['ok' => true]);
        $this->assertNotNull($verified->fresh()->attended_at);
        $this->actingAs($admin)->postJson(route('event.check-in.store', $event), ['code' => $pending->ticket_code])->assertStatus(422);
        $this->actingAs($admin)->postJson(route('event.check-in.store', $event), ['code' => 'SALAH123'])->assertNotFound();
        $this->actingAs($admin)->get(route('event.check-in', $event))->assertOk();

        $other = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id]);
        $this->actingAs($admin)->patch(route('event.attendance', $event), ['ids' => [$other->id, $pending->id], 'present' => 1])->assertSessionHas('success');
        $this->assertNotNull($other->fresh()->attended_at);
        $this->assertNull($pending->fresh()->attended_at, 'peserta belum terverifikasi tidak bisa ditandai hadir');
    }

    // ---------------- Sertifikat (8.9 alur 7–8)

    public function test_certificates_only_for_attendees_with_public_verification()
    {
        Storage::fake('local');
        Storage::fake('public');
        $event = $this->event(['status' => 'done', 'code' => 'PPL2611', 'starts_at' => '2026-11-01 08:00', 'ends_at' => '2026-11-01 16:00',
            'certificate_signatory' => [['name' => 'Dr. Ketua', 'title' => 'Ketua PD', 'signature' => null]]]);
        $attended = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id, 'attended_at' => now()]);
        $absent = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id]);

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.certificates', $event))->assertSessionHas('success'); // queue sync saat test

        $certificate = $attended->fresh()->certificateRecord;
        $this->assertNotNull($certificate);
        $this->assertNull($absent->fresh()->certificateRecord);
        $this->assertSame('001/SERT/PPL2611/RMS/2026', $certificate->number);
        $this->assertSame(12, strlen($certificate->verification_code));
        Storage::disk('local')->assertExists($certificate->file);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($certificate->file));
        Notification::assertSentTo($attended->user, CertificateIssued::class);

        $this->get("/sertifikat/{$certificate->verification_code}")->assertOk()->assertSee('VALID')->assertSee($attended->user->name)->assertSee($event->title)->assertSee($certificate->number);
        $this->get('/sertifikat/TIDAKADAKODE')->assertNotFound();

        // unduh: pemilik & pengelola; orang lain tidak
        $this->actingAs($attended->user)->get(route('certificate.download', $certificate))->assertOk();
        $this->actingAs($absent->user)->get(route('certificate.download', $certificate))->assertForbidden();

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('certificate.revoke', $certificate));
        $this->get("/sertifikat/{$certificate->verification_code}")->assertSee('DICABUT');
        $this->actingAs($attended->user)->get(route('certificate.download', $certificate))->assertForbidden();
    }

    public function test_certificates_require_done_status()
    {
        $event = $this->event(['status' => 'open']);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.certificates', $event))->assertStatus(422);
    }

    public function test_300_certificates_are_generated_through_the_queue_not_the_request()
    {
        Queue::fake();
        $event = $this->event(['status' => 'done', 'code' => 'BIG300']);
        $users = User::factory()->count(300)->create();
        Participant::insert($users->map(fn ($u, $i) => [
            'event_id' => $event->id, 'user_id' => $u->id, 'status' => 'verified', 'amount' => 0, 'attended_at' => now(),
            'ticket_code' => sprintf('T%09d', $i), 'created_at' => now(), 'updated_at' => now(),
        ])->all());

        $started = microtime(true);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('event.certificates', $event))->assertSessionHas('success');
        $this->assertLessThan(2, microtime(true) - $started, 'request hanya men-dispatch job');
        Queue::assertPushed(IssueEventCertificates::class, 1);
        Queue::assertNotPushed(GenerateCertificatePdf::class);
        $this->assertSame(0, Certificate::count());

        (new IssueEventCertificates($event))->handle(); // dijalankan worker
        Queue::assertPushed(GenerateCertificatePdf::class, 300);
        $this->assertSame(300, Certificate::distinct()->count('number'));
        $this->assertTrue(Certificate::where('number', '300/SERT/BIG300/RMS/' . $event->starts_at->year)->exists());
    }

    public function test_export_announcement_and_duplicate()
    {
        $event = $this->event();
        $admin = $this->user('pengurus@mail.com');
        $verified = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id]);
        $pending = Participant::factory()->create(['event_id' => $event->id, 'user_id' => $this->umum()->id, 'status' => 'pending']);

        $csv = $this->actingAs($admin)->get(route('event.participants.export', $event))->assertOk()->streamedContent();
        $this->assertStringContainsString($verified->user->email, $csv);
        $this->assertStringContainsString($verified->ticket_code, $csv);

        $this->actingAs($admin)->post(route('event.announce', $event), ['subject' => 'Ruang pindah', 'message' => 'Acara pindah ke ruang B.'])->assertSessionHas('success');
        Notification::assertSentTo($verified->user, EventAnnouncement::class);
        Notification::assertNotSentTo($pending->user, EventAnnouncement::class);

        $this->actingAs($admin)->post(route('event.duplicate', $event))->assertRedirect();
        $copy = Event::latest('id')->first();
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($event->code, $copy->code);
        $this->assertSame(0, $copy->participants()->count());
        $this->get("/agenda/{$copy->slug}")->assertNotFound(); // draf tidak publik
    }

    public function test_public_training_pages_and_home_upcoming_events()
    {
        $upcoming = $this->event(['title' => 'Webinar Mendatang Uji']);
        $done = $this->event(['title' => 'Event Lama Uji', 'status' => 'done', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()]);
        $draft = $this->event(['title' => 'Event Draf Uji', 'status' => 'draft']);

        $this->get('/agenda')->assertOk()->assertSee('Webinar Mendatang Uji')->assertDontSee('Event Lama Uji')->assertDontSee('Event Draf Uji');
        $this->get('/agenda?tab=selesai')->assertSee('Event Lama Uji')->assertDontSee('Webinar Mendatang Uji');
        $this->get('/agenda?jenis=' . $upcoming->type)->assertSee('Webinar Mendatang Uji');
        $this->get("/agenda/{$draft->slug}")->assertNotFound();
        $this->get('/')->assertSee('Webinar Mendatang Uji');
        $this->get('/sitemap.xml')->assertSee(route('site.training.show', $upcoming->slug), false);
    }
}
