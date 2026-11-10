<?php

namespace Tests\Feature\M3;

use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Member;
use App\Models\Skema;
use App\Models\SkemaEnrollment;
use App\Models\User;
use App\Notifications\AssignmentReviewed;
use App\Notifications\AssignmentSubmitted;
use App\Notifications\CertificateIssued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pembelajaran, penilaian 4 dimensi & sertifikat (PRD 9.6–9.7, AC M3). */
class LearningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        Storage::fake('local');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function skema(string $code): Skema
    {
        return Skema::where('code', $code)->firstOrFail();
    }

    private function running(): Batch
    {
        return Batch::where('status', 'running')->firstOrFail();
    }

    private function assess(Skema $skema, array $scoresByName): \Illuminate\Testing\TestResponse
    {
        $results = [];
        foreach ($scoresByName as $name => $scores) {
            $enrollment = SkemaEnrollment::where('skema_id', $skema->id)->whereHas('member', fn ($q) => $q->where('name', $name))->firstOrFail();
            $results[$enrollment->id] = array_combine(['knowledge', 'skill', 'character', 'social_cohesion'], $scores);
        }

        return $this->actingAs($this->user('pengurus@mail.com'))->post(route('assessment.store'), ['results' => $results]);
    }

    public function test_running_batch_enrolls_students_and_locked_skema_is_forbidden()
    {
        $demo = $this->user('anggota@mail.com');
        $statuses = $demo->member->enrollments()->with('skema')->get()->mapWithKeys(fn ($e) => [$e->skema->code => $e->status]);
        $this->assertSame(['FND' => 'berjalan', 'SIM' => 'terkunci', 'SIA' => 'terkunci', 'SIP' => 'terkunci', 'IT' => 'terkunci'], $statuses->all());

        $this->actingAs($demo)->get(route('learn.index'))->assertOk()->assertSee('Fundamental')->assertSee('Dibuka mentor');
        $this->actingAs($demo)->get(route('learn.skema', $this->skema('SIM')))->assertForbidden();
        $simLesson = $this->skema('SIM')->modules()->first()->lessons()->create(['title' => 'Menyusun SOP Dapur', 'type' => 'text']);
        $this->actingAs($demo)->get(route('learn.lesson', $simLesson))->assertForbidden();

        $this->actingAs($this->user('pengurus@mail.com'))->post(route('batch.open-skema', [$this->running(), $this->skema('SIM')]))->assertSessionHas('success', 'Skema Sistem Informasi Manajemen dibuka untuk 3 siswa.');
        $this->actingAs($demo)->get(route('learn.lesson', $simLesson))->assertOk()->assertSee('Menyusun SOP Dapur');
        $this->actingAs($this->user('anggota@mail.com'))->post(route('batch.open-skema', [$this->running(), $this->skema('SIA')]))->assertForbidden();

        // angkatan baru berstatus Berjalan → siswanya otomatis didaftarkan
        $batch = Batch::where('status', 'open')->firstOrFail();
        $member = Member::create(['member_number' => 'MTB-20279999', 'name' => 'Siswa Baru', 'category' => 'siswa', 'status' => 'aktif', 'batch_id' => $batch->id, 'integrity_pact_signed_at' => now()]);
        $this->actingAs($this->user('superadmin@mail.com'))->patch(route('batch.update', $batch), $batch->only(['code', 'name', 'quota']) + ['status' => 'running'])->assertSessionHasNoErrors();
        $this->assertSame(5, $member->enrollments()->count());
    }

    public function test_lessons_assignments_and_private_submission_files()
    {
        Notification::fake();
        $demo = $this->user('anggota@mail.com');
        $assignment = $this->skema('FND')->lessons()->where('type', 'assignment')->orderBy('lessons.id', 'desc')->first();
        $text = $this->skema('FND')->lessons()->where('type', 'text')->orderBy('lessons.id', 'desc')->first();

        $this->actingAs($demo)->post(route('learn.complete', $text))->assertSessionHas('success');
        $this->actingAs($demo)->post(route('learn.complete', $assignment))->assertStatus(422);
        $this->actingAs($demo)->post(route('learn.submit', $assignment), [])->assertSessionHasErrors('submission_file');
        $this->actingAs($demo)->post(route('learn.submit', $assignment), ['submission_file' => UploadedFile::fake()->create('sop.pdf', 200, 'application/pdf')])->assertSessionHasNoErrors();
        $progress = LessonProgress::where(['user_id' => $demo->id, 'lesson_id' => $assignment->id])->firstOrFail();
        Storage::disk('local')->assertExists($progress->submission_file);
        Notification::assertSentTo($this->user('pengurus@mail.com'), AssignmentSubmitted::class); // paket Pengurus Inti memuat learning-review

        // AC: file tugas hanya pemilik & learning-view
        $file = route('submission.file', $progress);
        $this->actingAs($demo)->get($file)->assertOk();
        $this->actingAs($this->user('pengurus@mail.com'))->get($file)->assertOk();
        $other = Member::where('name', 'Rizal Maulana')->firstOrFail();
        $otherUser = User::factory()->create()->assignRole('anggota');
        $other->update(['user_id' => $otherUser->id]);
        $this->actingAs($otherUser)->get($file)->assertForbidden();

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('submission.review', $progress), ['score' => 101, 'feedback' => 'x'])->assertSessionHasErrors('score');
        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('submission.review', $progress), ['score' => 88, 'feedback' => 'SOP rapi'])->assertRedirect(route('submission.index'));
        Notification::assertSentTo($demo, AssignmentReviewed::class);
        $this->actingAs($demo)->post(route('learn.submit', $assignment), ['submission_text' => 'perbaikan'])->assertStatus(422);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('submission.reopen', $progress))->assertSessionHas('success');
        $this->actingAs($demo)->post(route('learn.submit', $assignment), ['submission_text' => 'perbaikan'])->assertSessionHasNoErrors();
        $this->actingAs($demo)->get(route('learn.index'))->assertSee('%');
    }

    public function test_assessment_calculates_results_and_respects_locks()
    {
        Notification::fake();
        $fnd = $this->skema('FND');
        $this->assess($fnd, ['Anggota Demo' => [90, 90, 90, 90], 'Fajar Nugraha' => [95, 95, 65, 95]])->assertSessionHas('success', '2 nilai disimpan.');
        $demo = $this->user('anggota@mail.com')->member->enrollments()->where('skema_id', $fnd->id)->first();
        $fajar = Member::where('name', 'Fajar Nugraha')->first()->enrollments()->where('skema_id', $fnd->id)->first();
        $this->assertSame(['lulus', '90.00', 'Istimewa'], [$demo->fresh()->status, $demo->result->final_score, $demo->result->predicate]);
        $this->assertSame(['belum_lulus', false], [$fajar->fresh()->status, $fajar->result->passed]);

        // terbitkan: hanya yang lulus (AC: tidak terbit untuk passed = false)
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('assessment.issue'), ['batch' => $this->running()->id, 'skema' => $fnd->id])->assertSessionHas('success');
        $this->assertNull($fajar->result->fresh()->activeCertificate);
        $certificate = $demo->result->fresh()->activeCertificate;
        $this->assertMatchesRegularExpression('#^\d{3}/FND/RMS/[IVX]+/\d{4}$#', $certificate->number);
        $this->assertTrue($certificate->fresh()->isValid());
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($certificate->fresh()->file));
        Notification::assertSentTo($this->user('anggota@mail.com'), CertificateIssued::class);
        $this->assertNotNull($demo->result->fresh()->locked_at);

        // AC: ubah nilai terkunci → 422; nilai sama tidak masalah
        $this->assess($fnd, ['Anggota Demo' => [80, 90, 90, 90]])->assertStatus(422)->assertSee('Cabut sertifikat terlebih dahulu', false);
        $this->assess($fnd, ['Anggota Demo' => [90, 90, 90, 90]])->assertSessionHas('success');
        $this->assertSame(1, Certificate::where('series', 'FND')->where('member_id', $demo->member_id)->count(), 'terbit dua kali tidak membuat sertifikat ganda');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('assessment.issue'), ['batch' => $this->running()->id, 'skema' => $fnd->id]);
        $this->assertSame(1, Certificate::where('series', 'FND')->whereNull('revoked_at')->where('member_id', $demo->member_id)->count());
    }

    public function test_revoke_reissue_never_duplicates_numbers_and_public_verification()
    {
        $fnd = $this->skema('FND');
        $this->assess($fnd, ['Anggota Demo' => [90, 90, 90, 90], 'Fajar Nugraha' => [80, 80, 80, 80]]);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('assessment.issue'), ['batch' => $this->running()->id, 'skema' => $fnd->id]);
        $first = Certificate::where('member_id', $this->user('anggota@mail.com')->member->id)->firstOrFail();

        $this->get("/sertifikat/{$first->verification_code}")->assertOk()->assertSee('VALID')->assertSee('Anggota Demo')->assertSee('Skema Fundamental');
        $this->get('/sertifikat/TIDAKADAKODE')->assertNotFound();
        $this->actingAs($this->user('anggota@mail.com'))->get(route('certificate.download', $first))->assertOk();
        $this->actingAs(User::factory()->create()->assignRole('anggota'))->get(route('certificate.download', $first))->assertForbidden();

        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('certificate.revoke', $first), [])->assertSessionHasErrors('reason');
        $this->actingAs($this->user('pengurus@mail.com'))->patch(route('certificate.revoke', $first), ['reason' => 'Salah input nilai'])->assertSessionHas('success');
        $this->get("/sertifikat/{$first->verification_code}")->assertSee('DICABUT');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('certificate.download', $first))->assertForbidden();

        // nilai terbuka lagi → perbaiki → terbit ulang dengan nomor baru (T1)
        $this->assess($fnd, ['Anggota Demo' => [92, 90, 90, 90]])->assertSessionHas('success');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('certificate.reissue', $first))->assertSessionHas('success');
        // Rizal (data demo, lulus), Fajar, Anggota Demo (dicabut) + terbit ulang Anggota Demo
        $numbers = Certificate::where('series', 'FND')->pluck('number');
        $this->assertCount(4, $numbers);
        $this->assertSame($numbers->count(), $numbers->unique()->count());
        $this->assertSame(['001', '002', '003', '004'], $numbers->map(fn ($n) => substr($n, 0, 3))->sort()->values()->all());
        $this->assertNotNull($first->fresh()->revoked_at);
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('certificate.reissue', $first))->assertStatus(422);
    }

    public function test_incubation_graduation_after_all_skemas()
    {
        $member = $this->user('anggota@mail.com')->member;
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('certificate.graduate', $member))->assertStatus(422);

        SkemaEnrollment::where('member_id', $member->id)->update(['status' => 'lulus']);
        $this->actingAs($this->user('pengurus@mail.com'))->get(route('member.show', $member))->assertSee('Lulus Inkubasi')->assertSee('Kukuhkan');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('certificate.graduate', $member))->assertSessionHas('success');
        $member->refresh();
        $this->assertSame('magang', $member->phase);
        $this->assertSame('Fase Magang', $member->journeys()->where('type', 'fase')->whereNull('ended_at')->value('title'));
        $certificate = $member->certificates()->where('series', 'INK')->firstOrFail();
        $this->assertMatchesRegularExpression('#^001/INK/RMS/#', $certificate->number);
        $this->get("/sertifikat/{$certificate->verification_code}")->assertSee('Kelulusan Inkubasi');
        $this->actingAs($this->user('pengurus@mail.com'))->post(route('certificate.graduate', $member))->assertStatus(422);
        $this->actingAs($this->user('anggota@mail.com'))->get(route('certificate.index'))->assertSee($certificate->number);
    }

    public function test_curriculum_management_and_public_pages()
    {
        $pengurus = $this->user('pengurus@mail.com');
        $sia = $this->skema('SIA');
        $module = $sia->modules()->first();
        $this->actingAs($pengurus)->post(route('lesson.store', $module), ['title' => 'Video COA', 'type' => 'video', 'video_url' => 'https://vimeo.com/123'])->assertSessionHasErrors('video_url');
        $this->actingAs($pengurus)->post(route('lesson.store', $module), ['title' => 'Video COA', 'type' => 'video', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_published' => 1])->assertSessionHasNoErrors();
        $this->actingAs($pengurus)->post(route('lesson.store', $module), ['title' => 'Berkas', 'type' => 'file'])->assertSessionHasErrors('file');
        $this->actingAs($pengurus)->post(route('module.store', $sia), ['title' => 'Studi Kasus Dapur'])->assertSessionHasNoErrors();

        $ids = $sia->modules()->pluck('id')->reverse()->values()->all();
        $this->actingAs($pengurus)->post(route('skema.reorder', $sia), ['modules' => $ids])->assertRedirect(route('skema.show', $sia));
        $this->assertSame('Studi Kasus Dapur', $sia->modules()->first()->title);
        $this->actingAs($pengurus)->post(route('skema.store'), ['code' => 'INK', 'name' => 'X'])->assertSessionHasErrors('code');
        $this->actingAs($this->user('anggota@mail.com'))->get(route('skema.index'))->assertForbidden();

        $this->get('/kurikulum')->assertOk()->assertSee('Sistem Informasi Akuntansi')->assertSee('Studi Kasus Dapur')->assertSee('Peer-auditing');
        $this->get('/')->assertSee('Teknologi Bisnis')->assertSee('Bulan 2–3');
        $sia->update(['is_public' => false]);
        $this->get('/kurikulum')->assertDontSee('Sistem Informasi Akuntansi');
    }
}
