<?php

namespace Tests\Feature\M2;

use App\Models\Announcement;
use App\Models\BusinessUnit;
use App\Models\HealthRecord;
use App\Models\Member;
use App\Models\Page;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Notifications\SeriousViolation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Data anggota, pembinaan, kesehatan, pengumuman (PRD 9.4, 9.5, 9.19, AC M2). */
class MemberTest extends TestCase
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

    private function demoMember(): Member
    {
        return $this->user('anggota@mail.com')->member;
    }

    private function other(): Member
    {
        return Member::where('name', 'Rizal Maulana')->firstOrFail();
    }

    public function test_member_cannot_open_other_member_detail()
    {
        $anggota = $this->user('anggota@mail.com');
        $this->actingAs($anggota)->get(route('member.show', $this->other()))->assertForbidden();
        $this->actingAs($anggota)->get(route('member.show', $this->demoMember()))->assertForbidden(); // anggota memakai Profil Saya
        $this->actingAs($anggota)->get(route('member.datatable'))->assertForbidden();
        $this->actingAs($anggota)->get(route('profile.index'))->assertOk()->assertSee($this->demoMember()->member_number)->assertSee('Membantu rekan');
    }

    public function test_private_columns_need_member_private()
    {
        $this->other()->update(['nik' => '3203010000000001', 'phone' => '0812999', 'address' => 'Jl. Rahasia']);
        $viewer = User::factory()->create()->assignRole('pengurus')->givePermissionTo(['member-view', 'member-export']);

        $json = $this->actingAs($viewer)->getJson(route('member.datatable', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk()->json('data');
        $this->assertNotEmpty($json);
        foreach (['nik', 'phone', 'address', 'district', 'birth_date', 'father_name'] as $field) {
            $this->assertArrayNotHasKey($field, $json[0], "{$field} bocor tanpa member-private");
        }
        $csv = $this->actingAs($viewer)->get(route('member.export'))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('nik', strtok($csv, "\n"));
        $this->assertStringNotContainsString('3203010000000001', $csv);
        $this->actingAs($viewer)->get(route('member.show', $this->other()))->assertOk()->assertDontSee('Jl. Rahasia');

        $private = $this->user('pengurus@mail.com'); // paket Pengurus Inti memuat member-private
        $this->assertStringContainsString('3203010000000001', $this->actingAs($private)->get(route('member.export'))->streamedContent());
        $this->actingAs($private)->get(route('member.show', $this->other()))->assertSee('Jl. Rahasia');
    }

    public function test_status_effects_and_expelled_member_cannot_log_in()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $member = $this->demoMember();
        $this->actingAs($pengurus)->patch(route('member.status', $member), ['status' => 'dikeluarkan'])->assertSessionHasErrors('reason');
        $this->actingAs($pengurus)->patch(route('member.status', $member), ['status' => 'dikeluarkan', 'reason' => 'Pelanggaran berat'])->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertNotNull($member->left_at);
        $this->assertFalse($member->user->is_active);
        auth()->logout();
        $this->post('/login', ['email' => 'anggota@mail.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($pengurus)->patch(route('member.status', $member), ['status' => 'lulus', 'reason' => 'Lulus S1 UT'])->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertSame(['alumni', 'alumni', true], [$member->category, $member->phase, $member->user->is_active]);
    }

    public function test_website_shows_only_members_who_allow_it()
    {
        $page = Page::where('slug', 'tentang')->firstOrFail();
        $page->update(['blocks' => [['type' => 'members', 'data' => ['title' => 'Siswa Kami', 'category' => 'semua', 'limit' => 8]]]]);
        $this->get('/tentang')->assertDontSee('Siswa Kami'); // belum ada yang mengizinkan → blok tidak tampil

        $this->other()->update(['show_on_site' => true]);
        $this->get('/tentang')->assertSee('Siswa Kami')->assertSee('Rizal Maulana')->assertDontSee('Fajar Nugraha')->assertDontSee('Anggota Demo');
    }

    public function test_journeys_keep_one_active_placement_and_phase_change_is_recorded()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $member = $this->other();
        [$kebun, $dapur] = BusinessUnit::whereIn('name', ['Kebun', 'Dapur'])->orderBy('name', 'desc')->get();

        $this->actingAs($pengurus)->post(route('member.journeys.store', $member), ['type' => 'penempatan', 'title' => 'Staf kebun', 'business_unit_id' => $kebun->id, 'started_at' => today()->subMonth()->toDateString()])->assertSessionHasNoErrors();
        $this->actingAs($pengurus)->post(route('member.journeys.store', $member), ['type' => 'penempatan', 'title' => 'Staf dapur', 'business_unit_id' => $dapur->id, 'started_at' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame(1, $member->journeys()->where('type', 'penempatan')->whereNull('ended_at')->count());
        $this->assertSame('Dapur', $member->fresh()->activePlacement->businessUnit->name);
        $this->actingAs($pengurus)->post(route('member.journeys.store', $member), ['type' => 'penempatan', 'title' => 'Tanpa unit', 'started_at' => today()->toDateString()])->assertSessionHasErrors('business_unit_id');

        $this->actingAs($pengurus)->patch(route('member.update', $member), ['name' => 'Rizal Maulana', 'category' => 'siswa', 'phase' => 'magang'])->assertSessionHasNoErrors();
        $this->assertSame(['Fase Magang'], $member->journeys()->where('type', 'fase')->whereNull('ended_at')->pluck('title')->all());
    }

    public function test_integrity_pact_is_required_once()
    {
        $member = $this->demoMember();
        $member->update(['integrity_pact_signed_at' => null]);
        $user = $member->user;

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('pact.show'));
        $this->actingAs($user)->get(route('pact.show'))->assertOk()->assertSee('Pakta Integritas');
        $this->actingAs($user)->post(route('pact.sign'))->assertSessionHasErrors('agree');
        $this->actingAs($user)->post(route('pact.sign'), ['agree' => '1'])->assertRedirect(route('home'));
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertNotNull($member->fresh()->integrity_pact_signed_at);
    }

    public function test_coaching_notes_for_many_students_and_violation_alert()
    {
        Notification::fake();
        $pengurus = $this->user('pengurus@mail.com');
        $ids = Member::where('category', 'siswa')->pluck('id')->all();

        $this->actingAs($pengurus)->post(route('coaching.store'), ['member_ids' => $ids, 'date' => today()->toDateString(), 'type' => 'positif', 'dimension' => 'kontribusi_tim',
            'body' => 'Gotong royong membersihkan kolam', 'visibility' => 'anggota'])->assertSessionHasNoErrors();
        $this->assertSame(count($ids), \App\Models\CoachingNote::where('body', 'Gotong royong membersihkan kolam')->count());

        $member = $this->other();
        foreach (range(1, 3) as $i) {
            $this->actingAs($pengurus)->post(route('coaching.store'), ['member_ids' => [$member->id], 'date' => today()->toDateString(), 'type' => 'pelanggaran_sedang',
                'dimension' => 'disiplin', 'body' => "Terlambat piket {$i}", 'visibility' => 'privat']);
        }
        $this->assertTrue($member->hasViolationAlert());
        Notification::assertSentTo($this->user('superadmin@mail.com'), SeriousViolation::class);
        Notification::assertSentTo($pengurus, SeriousViolation::class); // pemegang member-update
        Notification::assertNotSentTo($this->user('anggota@mail.com'), SeriousViolation::class);

        // catatan privat tidak terlihat oleh anggota sendiri
        $this->actingAs($this->user('anggota@mail.com'))->get(route('profile.index'))->assertSee('Gotong royong')->assertDontSee('Terlambat piket');
        $this->actingAs($this->user('anggota@mail.com'))->post(route('coaching.store'), ['member_ids' => [$member->id]])->assertForbidden();
    }

    public function test_health_records_are_private()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $member = $this->demoMember();
        $this->actingAs($pengurus)->post(route('health.store'), ['member_id' => $member->id, 'date' => today()->toDateString(), 'complaint' => 'Demam', 'action' => 'puskesmas', 'cost' => 25000])
            ->assertRedirect(route('member.show', [$member, 'tab' => 'kesehatan']));
        $this->assertSame(1, HealthRecord::count());

        $noHealth = User::factory()->create()->assignRole('pengurus')->givePermissionTo(['member-view']);
        $this->actingAs($noHealth)->get(route('health.index'))->assertForbidden();
        $this->actingAs($noHealth)->get(route('member.show', [$member, 'tab' => 'kesehatan']))->assertOk()->assertDontSee('Demam');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('profile.index'))->assertSee('Demam');
    }

    public function test_announcements_reach_their_audience()
    {
        Notification::fake();
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('announcement.store'), ['title' => 'Rapat pengurus Jumat', 'body' => 'Di aula.', 'audience' => 'pengurus',
            'published_at' => now()->subMinute()->format('Y-m-d\TH:i'), 'send_email' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('announcement.store'), ['title' => 'Draf', 'body' => 'x', 'audience' => 'semua'])->assertSessionHasNoErrors();

        $this->actingAs($this->user('superadmin@mail.com'))->get('/dashboard')->assertSee('Rapat pengurus Jumat')->assertDontSee('>Draf<', false);
        $this->actingAs($this->user('anggota@mail.com'))->get('/dashboard')->assertDontSee('Rapat pengurus Jumat')->assertSee('Selamat datang di sistem');
        Notification::assertSentTo($this->user('pengurus@mail.com'), AnnouncementPublished::class);
        Notification::assertNotSentTo($this->user('anggota@mail.com'), AnnouncementPublished::class);

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('announcement.store'), ['title' => 'X', 'body' => 'x', 'audience' => 'angkatan'])->assertSessionHasErrors('batch_id');
    }

    public function test_csv_import_and_member_card()
    {
        $csv = "name,email,category,status,phase,batch,joined_at,nik,gender\n"
            . "Hasan Alumni,hasan@contoh.id,alumni,lulus,alumni,2026-2,2025-01-10,3203010000000099,L\n"
            . "Relawan Tanpa Akun,,relawan,,,,,,\n"
            . "Salah Kategori,,dosen,,,,,,\n"
            . "Email Ganda,anggota@mail.com,siswa,,,,,,\n";
        $file = UploadedFile::fake()->createWithContent('anggota.csv', $csv);

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('member.import'), ['file' => $file])
            ->assertRedirect(route('member.index'))->assertSessionHas('warning', '2 anggota diimpor. 2 baris dilewati.')
            ->assertSessionHas('import_errors', fn ($e) => str_starts_with($e[0], 'Baris 4:') && str_starts_with($e[1], 'Baris 5:'));
        $hasan = Member::where('name', 'Hasan Alumni')->firstOrFail();
        $this->assertStringStartsWith('MTB-2025', $hasan->member_number);
        $this->assertTrue($hasan->user->hasRole('anggota'));
        $this->assertNull(Member::where('name', 'Relawan Tanpa Akun')->value('user_id'));

        $card = $this->actingAs($this->user('pengurus@mail.com'))->get(route('member.card', $hasan))->assertOk();
        $this->assertSame('application/pdf', $card->headers->get('Content-Type'));
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('member.verify', $hasan->member_number))->assertOk()->assertSee('Hasan Alumni');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('member.verify', $hasan->member_number))->assertForbidden();
    }

    public function test_member_documents_are_private_and_notifications_can_be_read()
    {
        Storage::fake('local');
        $member = $this->other();
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('member.documents.store', $member), ['key' => 'pakta', 'file' => UploadedFile::fake()->create('pakta.pdf', 50, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $url = route('member.document', [$member, 'pakta']);
        $this->actingAs($this->user('pengurus@mail.com'))->get($url)->assertOk();
        $viewer = User::factory()->create()->assignRole('pengurus')->givePermissionTo(['member-view']);
        $this->actingAs($viewer)->get($url)->assertForbidden();

        $admin = $this->user('superadmin@mail.com');
        $admin->notifications()->delete(); // data demo (mis. voucher diajukan) tidak ikut dihitung
        $admin->notify(new SeriousViolation($member));
        $id = $admin->unreadNotifications()->value('id');
        $this->actingAs($admin)->get('/dashboard')->assertSee('Perhatian pembinaan');
        $this->actingAs($admin)->get(route('notification.read', $id))->assertRedirect(route('member.show', $member) . '?tab=pembinaan');
        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('notification.read', $id))->assertNotFound();
    }
}
