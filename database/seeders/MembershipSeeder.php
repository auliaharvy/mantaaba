<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Applicant;
use App\Models\Batch;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Contoh angkatan, pendaftar, dan anggota untuk dev/presentasi (PRD 13.5). Tidak dijalankan di produksi. */
class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 500, 'MembershipSeeder dilarang di produksi.');

        $running = Batch::create(['code' => '2026-2', 'name' => 'Angkatan 2 Tahun 2026', 'status' => 'running', 'quota' => 6,
            'admission_opens_at' => today()->subMonths(4), 'admission_closes_at' => today()->subMonths(3), 'starts_at' => today()->subMonths(2), 'ends_at' => today()->addMonths(4)]);
        $open = Batch::create(['code' => '2027-1', 'name' => 'Angkatan 1 Tahun 2027', 'status' => 'open', 'quota' => 6,
            'admission_opens_at' => today()->subWeek(), 'admission_closes_at' => today()->addMonth(), 'starts_at' => today()->addMonths(2), 'ends_at' => today()->addMonths(8)]);

        $students = [
            ['Anggota Demo', User::where('email', 'anggota@mail.com')->first(), 'Sigaling'],
            ['Rizal Maulana', null, 'Sigaling'],
            ['Fajar Nugraha', null, 'Cibeber'],
        ];
        foreach ($students as [$name, $user, $district]) {
            $member = Member::create(['user_id' => $user?->id, 'member_number' => Member::nextNumber(2026), 'name' => $name, 'category' => 'siswa', 'status' => 'aktif',
                'phase' => 'inkubasi', 'batch_id' => $running->id, 'joined_at' => $running->starts_at, 'district' => $district, 'gender' => 'L', 'integrity_pact_signed_at' => now()]);
            $member->startPhase('inkubasi');
        }
        Member::first()->coachingNotes()->create(['author_id' => User::where('email', 'pengurus@mail.com')->value('id'), 'date' => today()->subDay(), 'type' => 'positif',
            'dimension' => 'kontribusi_tim', 'body' => 'Membantu rekan menyelesaikan pencatatan dapur.', 'visibility' => 'anggota']);

        foreach ([['Ahmad Fauzi', 'baru', 'lengkap', 'mampu'], ['Dimas Pratama', 'verifikasi_berkas', 'yatim', 'kurang_mampu'], ['Yusuf Hidayat', 'wawancara', 'lengkap', 'kurang_mampu']] as $i => [$name, $status, $family, $economy]) {
            Applicant::create([
                'batch_id' => $open->id, 'registration_number' => sprintf('PMB-%s-%03d', $open->code, $i + 1), 'name' => $name, 'nik' => '32030' . str_pad((string) ($i + 1), 11, '0', STR_PAD_LEFT),
                'birth_place' => 'Cianjur', 'birth_date' => today()->subYears(18 + $i), 'gender' => 'L', 'phone' => '08123456780' . $i, 'address' => 'Kp. Contoh RT 01/02',
                'village' => 'Sigaling', 'district' => $i === 0 ? 'Cibeber' : 'Sigaling', 'regency' => 'Cianjur', 'education' => 'SMA', 'guardian_phone' => '08129876540' . $i,
                'family_status' => $family, 'economic_status' => $economy, 'motivation' => str_repeat('Saya ingin belajar bisnis dan akuntansi sambil membina karakter di asrama. ', 3),
                'info_source' => 'Masjid / pengajian', 'documents' => [], 'is_priority' => $i > 0, 'status' => $status,
                'history' => [['status' => 'baru', 'note' => 'Formulir online', 'by' => null, 'at' => now()->subDays(5 - $i)->toDateTimeString()]],
            ]);
        }

        // pembelajaran contoh: materi FND, siswa angkatan berjalan terdaftar ke skema, satu tugas terkirim, satu nilai lulus
        $fnd = \App\Models\Skema::where('code', 'FND')->first();
        // materi contoh hanya untuk modul (elemen) dari 2 unit kompetensi pertama
        foreach ($fnd?->modules()->whereIn('competency_unit_id', $fnd->units()->limit(2)->pluck('id'))->get() ?? [] as $module) {
            $module->lessons()->create(['title' => 'Pengantar ' . $module->title, 'type' => 'text', 'body' => '<p>Bacalah pengantar modul ini dan diskusikan bersama kelompok.</p>', 'sort_order' => 0]);
            $module->lessons()->create(['title' => 'Tugas ' . $module->title, 'type' => 'assignment', 'body' => '<p>Kerjakan latihan lalu unggah hasilnya.</p>', 'due_days' => 7, 'sort_order' => 1]);
        }
        app(\App\Services\SkemaEnroller::class)->enrollBatch($running);
        if ($fnd && ($demo = User::where('email', 'anggota@mail.com')->first())) {
            $first = $fnd->lessons()->orderBy('lessons.id')->first();
            \App\Models\LessonProgress::create(['user_id' => $demo->id, 'lesson_id' => $first->id, 'completed_at' => now()]);
            \App\Models\LessonProgress::create(['user_id' => $demo->id, 'lesson_id' => $first->id + 1, 'completed_at' => now(), 'submitted_at' => now(), 'submission_text' => 'Ringkasan hasil diskusi kelompok.']);
        }
        $rizal = Member::where('name', 'Rizal Maulana')->first()?->enrollments()->where('skema_id', $fnd?->id)->first();
        if ($rizal) {
            $scores = ['knowledge' => 82, 'skill' => 85, 'character' => 88, 'social_cohesion' => 90];
            \App\Models\SkemaResult::create(['enrollment_id' => $rizal->id, 'assessed_at' => now()] + $scores + \App\Services\SkemaScore::calculate($scores, \App\Models\Setting::DEFAULT_WEIGHTS, 70, 70));
            $rizal->update(['status' => 'lulus']);
        }

        Announcement::create(['title' => 'Selamat datang di sistem Rumah Mantaaba', 'body' => 'Silakan lengkapi profil dan cek jadwal piket setiap pagi.', 'audience' => 'siswa',
            'is_pinned' => true, 'published_at' => now()->subDay(), 'created_by' => User::where('email', 'pengurus@mail.com')->value('id')]);
    }
}
