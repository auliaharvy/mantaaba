<?php

namespace Tests\Feature\M2;

use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use App\Notifications\ApplicantStatusChanged;
use App\Notifications\NewApplicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Angkatan & penerimaan (PRD 9.2–9.3, AC M2). */
class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        Storage::fake('public');
        RateLimiter::clear('admission');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function openBatch(): Batch
    {
        return Batch::where('status', 'open')->firstOrFail();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Rizky Ramadhan', 'nik' => '3203011234567890', 'birth_place' => 'Cianjur', 'birth_date' => now()->subYears(19)->toDateString(), 'gender' => 'L',
            'phone' => '0812 3333 4444', 'email' => 'rizky@contoh.id', 'address' => 'Kp. Babakan RT 02/03', 'village' => 'Sigaling', 'district' => 'Sigaling', 'regency' => 'Cianjur',
            'education' => 'SMA', 'school_name' => 'SMAN 1', 'graduation_year' => now()->year, 'father_name' => 'Ayah', 'mother_name' => 'Ibu', 'guardian_phone' => '0812 5555 6666',
            'family_status' => 'lengkap', 'economic_status' => 'mampu', 'motivation' => str_repeat('Saya ingin menjadi pengusaha jujur yang taat pajak. ', 3),
            'info_source' => 'Website', 'agree_dorm' => '1', 'agree_pact' => '1', 'agree_privacy' => '1',
            'documents' => ['photo' => UploadedFile::fake()->image('foto.jpg'), 'id_card' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
                'parent_consent' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf')],
        ], $override);
    }

    private function applicantAt(string $status): Applicant
    {
        $applicant = Applicant::where('status', 'wawancara')->firstOrFail();
        $applicant->update(['status' => $status]);

        return $applicant;
    }

    public function test_public_form_creates_applicant_with_private_files_and_receipt()
    {
        Notification::fake();
        $this->get('/pendaftaran')->assertOk()->assertSee('Kirim Pendaftaran')->assertSee($this->openBatch()->name);

        $response = $this->post('/pendaftaran', $this->payload());
        $applicant = Applicant::where('nik', '3203011234567890')->firstOrFail();
        $this->assertSame('PMB-2027-1-004', $applicant->registration_number);
        $this->assertFalse($applicant->is_priority, 'keluarga lengkap, mampu, kecamatan prioritas belum diatur');
        $response->assertRedirectContains('/pendaftaran/selesai/PMB-2027-1-004');
        Storage::disk('local')->assertExists($applicant->documents['photo']);
        $this->assertEmpty(Storage::disk('public')->allFiles(), 'berkas pendaftar tidak di disk publik');
        Notification::assertSentTo($this->user('pengurus@mail.com'), NewApplicant::class);
        Notification::assertNotSentTo($this->user('anggota@mail.com'), NewApplicant::class);

        $this->get($response->headers->get('Location'))->assertOk()->assertSee('PMB-2027-1-004');
        $this->get('/pendaftaran/selesai/PMB-2027-1-004')->assertForbidden(); // tanpa tanda tangan
        $receipt = URL::temporarySignedRoute('site.admission.receipt', now()->addHour(), 'PMB-2027-1-004');
        $this->assertSame('application/pdf', $this->get($receipt)->assertOk()->headers->get('Content-Type'));
    }

    public function test_duplicate_nik_in_same_batch_is_rejected_and_honeypot_is_silent()
    {
        $this->post('/pendaftaran', $this->payload())->assertSessionHasNoErrors();
        $this->post('/pendaftaran', $this->payload(['email' => 'lain@contoh.id']))->assertSessionHasErrors(['nik' => 'NIK sudah terdaftar pada angkatan ini.']);
        $this->post('/pendaftaran', $this->payload(['nik' => '3203019999999999', 'website' => 'spam']))->assertRedirect('/pendaftaran');
        $this->assertDatabaseMissing('applicants', ['nik' => '3203019999999999']);

        $this->post('/pendaftaran', $this->payload(['nik' => '123', 'motivation' => 'pendek', 'agree_pact' => null, 'documents' => []]))
            ->assertSessionHasErrors(['nik', 'motivation', 'agree_pact', 'documents.photo', 'documents.id_card']);
    }

    public function test_priority_follows_setting_districts_and_family()
    {
        \App\Models\Setting::current()->update(['priority_districts' => ['Cibeber']]);
        $this->post('/pendaftaran', $this->payload(['district' => 'cibeber ']))->assertSessionHasNoErrors();
        $this->assertTrue(Applicant::where('nik', '3203011234567890')->value('is_priority'));
        $this->post('/pendaftaran', $this->payload(['nik' => '3203011234567891', 'district' => 'Lain']))->assertSessionHasNoErrors();
        $this->assertFalse(Applicant::where('nik', '3203011234567891')->value('is_priority'));
    }

    public function test_form_hidden_outside_open_window()
    {
        $this->openBatch()->update(['admission_closes_at' => today()->subDay()]);
        $this->get('/pendaftaran')->assertOk()->assertSee('Pendaftaran belum dibuka')->assertDontSee('Kirim Pendaftaran');
        $this->post('/pendaftaran', $this->payload())->assertNotFound();
    }

    public function test_status_check_uses_uniform_message()
    {
        $applicant = Applicant::firstOrFail();
        $this->post('/pendaftaran/status', ['registration_number' => strtolower($applicant->registration_number), 'birth_date' => $applicant->birth_date->toDateString()])
            ->assertOk()->assertSee(Applicant::PUBLIC_STATUS[$applicant->status])->assertDontSee('skor');
        $wrongDate = $this->post('/pendaftaran/status', ['registration_number' => $applicant->registration_number, 'birth_date' => '2000-01-01'])->assertOk()->getContent();
        $wrongNumber = $this->post('/pendaftaran/status', ['registration_number' => 'PMB-0000-0-000', 'birth_date' => $applicant->birth_date->toDateString()])->assertOk()->getContent();
        $this->assertStringContainsString('Data tidak ditemukan', $wrongDate);
        $this->assertStringContainsString('Data tidak ditemukan', $wrongNumber);
    }

    public function test_applicant_files_need_login_and_admission_view()
    {
        $this->post('/pendaftaran', $this->payload());
        $applicant = Applicant::where('nik', '3203011234567890')->firstOrFail();
        $url = route('applicant.document', [$applicant, 'photo']);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->user('anggota@mail.com'))->get($url)->assertForbidden();
        $this->actingAs($this->user('pengurus@mail.com'))->get($url)->assertOk();
        $this->actingAs($this->user('anggota@mail.com'))->get(route('applicant.show', $applicant))->assertForbidden();
    }

    public function test_selection_moves_forward_with_note_and_whatsapp_link()
    {
        Notification::fake();
        $applicant = Applicant::where('status', 'baru')->firstOrFail();
        $applicant->update(['email' => 'calon@contoh.id']);
        $pengurus = $this->user('pengurus@mail.com');

        $this->actingAs($pengurus)->post(route('applicant.move', $applicant), ['status' => 'verifikasi_berkas'])->assertSessionHasErrors('note');
        $this->actingAs($pengurus)->post(route('applicant.move', $applicant), ['status' => 'wawancara', 'note' => 'Berkas lengkap', 'scheduled_at' => now()->addDays(3)->format('Y-m-d\TH:i')])
            ->assertSessionHas('wa_link', fn ($link) => str_starts_with($link, 'https://wa.me/62') && str_contains(urldecode($link), 'Jadwal wawancara'));
        $applicant->refresh();
        $this->assertSame('wawancara', $applicant->status);
        $this->assertNotNull($applicant->interview_at);
        $this->assertSame('Berkas lengkap', last($applicant->history)['note']);
        Notification::assertSentOnDemand(ApplicantStatusChanged::class);

        $this->actingAs($pengurus)->post(route('applicant.move', $applicant), ['status' => 'verifikasi_berkas', 'note' => 'mundur'])->assertSessionHasErrors('status');
        $this->actingAs($pengurus)->patch(route('applicant.scores', $applicant), ['scores' => ['motivasi' => 5, 'komitmen' => 4, 'wawancara' => 6]])->assertSessionHasErrors('scores.wawancara');
        $this->actingAs($pengurus)->patch(route('applicant.scores', $applicant), ['scores' => ['motivasi' => 5, 'komitmen' => 4]])->assertSessionHasNoErrors();
        $this->assertSame(4.5, $applicant->fresh()->averageScore());
    }

    public function test_accept_creates_account_member_and_moves_files()
    {
        $this->post('/pendaftaran', $this->payload());
        $applicant = Applicant::where('nik', '3203011234567890')->firstOrFail();
        $applicant->update(['status' => 'wawancara']);
        $oldPhoto = $applicant->documents['photo'];

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('applicant.accept', $applicant), ['note' => 'Lulus seleksi'])
            ->assertSessionHas('wa_link')->assertSessionHas('password_link');

        $applicant->refresh();
        $member = $applicant->member;
        $this->assertSame('diterima', $applicant->status);
        $this->assertMatchesRegularExpression('/^MTB-\d{8}$/', $member->member_number);
        $this->assertSame(['siswa', 'aktif', 'inkubasi', '3203011234567890'], [$member->category, $member->status, $member->phase, $member->nik]);
        $this->assertTrue($member->user->hasRole('anggota'));
        $this->assertSame('rizky@contoh.id', $member->user->email);
        $this->assertTrue(Password::broker()->tokenExists($member->user, basename(parse_url(session('password_link'), PHP_URL_PATH))));
        Storage::disk('local')->assertMissing($oldPhoto);
        Storage::disk('local')->assertExists($member->documents['photo']);
        $this->assertNotNull($member->photo);
        $this->assertSame(1, $member->journeys()->where('type', 'fase')->count());
        // siswa baru wajib menyetujui pakta integritas
        $this->actingAs($member->user)->get('/dashboard')->assertRedirect(route('pact.show'));
    }

    public function test_quota_is_locked_and_decisions_are_final()
    {
        $batch = $this->openBatch();
        $batch->update(['quota' => 1]);
        $first = $this->applicantAt('wawancara');
        $second = Applicant::where('status', 'verifikasi_berkas')->firstOrFail();
        $second->update(['status' => 'kunjungan_rumah']);
        $decider = $this->user('pengurus@mail.com');

        $this->actingAs($decider)->post(route('applicant.accept', $first))->assertSessionHasNoErrors();
        $this->actingAs($decider)->post(route('applicant.accept', $second))->assertSessionHasErrors(['status' => 'Kuota angkatan penuh.']);
        $this->assertSame(1, $batch->acceptedCount());
        $this->actingAs($decider)->post(route('applicant.accept', $first->fresh()))->assertSessionHasErrors('status');

        $this->actingAs($decider)->post(route('applicant.reject', $second), ['status' => 'ditolak', 'note' => 'Kuota penuh'])->assertSessionHasNoErrors();
        $this->actingAs($decider)->post(route('applicant.reject', $second), ['status' => 'ditolak', 'note' => 'lagi'])->assertSessionHasErrors('status');

        $baru = Applicant::where('status', 'baru')->firstOrFail();
        $this->actingAs($decider)->post(route('applicant.accept', $baru))->assertSessionHasErrors('status');

        $noDecide = User::factory()->create()->assignRole('pengurus')->givePermissionTo(['admission-view', 'admission-update']);
        $this->actingAs($noDecide)->post(route('applicant.reject', $baru), ['status' => 'ditolak', 'note' => 'x'])->assertForbidden();
    }

    public function test_rejected_applicants_are_purged_after_twelve_months()
    {
        $applicant = Applicant::firstOrFail();
        Storage::disk('local')->put("applicants/{$applicant->registration_number}/foto.jpg", 'x');
        $applicant->update(['status' => 'ditolak', 'decided_at' => now()]);

        $this->artisan('applicants:purge')->assertSuccessful();
        $this->assertModelExists($applicant);

        $this->travel(13)->months();
        $this->artisan('applicants:purge')->expectsOutput('1 pendaftar dihapus.')->assertSuccessful();
        $this->assertDatabaseMissing('applicants', ['id' => $applicant->id]);
        Storage::disk('local')->assertMissing("applicants/{$applicant->registration_number}/foto.jpg");
    }

    public function test_only_one_open_batch_and_export_permission()
    {
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('batch.store'), ['code' => '2027-2', 'name' => 'Angkatan 2', 'quota' => 6, 'status' => 'open'])
            ->assertSessionHasErrors('status');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('batch.store'), ['code' => '2027-2', 'name' => 'Angkatan 2', 'quota' => 6, 'status' => 'draft'])
            ->assertSessionHasNoErrors();

        $csv = $this->actingAs($this->user('pengurus@mail.com'))->get(route('applicant.export', ['batch' => $this->openBatch()->id]))->assertOk()->streamedContent();
        $this->assertStringContainsString('registration_number', $csv);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('applicant.export', ['batch' => $this->openBatch()->id]))->assertForbidden();
    }
}
