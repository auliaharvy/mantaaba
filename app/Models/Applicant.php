<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Pendaftar siswa baru (PRD 9.3). Data pribadi & berkas privat (disk local). */
class Applicant extends Model
{
    use LogsActivity, SoftDeletes;

    public const STATUSES = ['baru' => 'Baru', 'verifikasi_berkas' => 'Verifikasi berkas', 'wawancara' => 'Wawancara', 'kunjungan_rumah' => 'Kunjungan rumah',
        'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'mengundurkan_diri' => 'Mengundurkan diri'];

    /** Tahap seleksi berurutan (bisa melompati kunjungan rumah). */
    public const STAGES = ['baru', 'verifikasi_berkas', 'wawancara', 'kunjungan_rumah'];

    public const FINAL = ['diterima', 'ditolak', 'mengundurkan_diri'];

    /** Bahasa ramah untuk cek status publik (tanpa skor/catatan internal). */
    public const PUBLIC_STATUS = [
        'baru' => 'Pendaftaran Anda sudah kami terima dan menunggu pemeriksaan berkas.',
        'verifikasi_berkas' => 'Berkas Anda sedang diperiksa oleh panitia.',
        'wawancara' => 'Anda masuk tahap wawancara. Panitia akan menghubungi melalui WhatsApp.',
        'kunjungan_rumah' => 'Anda masuk tahap kunjungan rumah. Panitia akan menghubungi melalui WhatsApp.',
        'diterima' => 'Selamat, Anda diterima! Panitia akan mengirimkan informasi akun dan jadwal masuk asrama.',
        'ditolak' => 'Mohon maaf, Anda belum dapat diterima pada angkatan ini. Terima kasih atas minat Anda.',
        'mengundurkan_diri' => 'Pendaftaran Anda tercatat mengundurkan diri.',
    ];

    public const SCORES = ['motivasi' => 'Motivasi', 'komitmen' => 'Komitmen', 'kondisi_keluarga' => 'Kondisi keluarga', 'wawancara' => 'Hasil wawancara', 'kunjungan' => 'Hasil kunjungan'];

    /** Berkas => [label, wajib]. jpg/png/pdf maks. 2 MB. */
    public const DOCUMENTS = ['photo' => ['Pas foto', true], 'id_card' => ['KTP / Kartu Keluarga', true], 'diploma' => ['Ijazah / SKL', false],
        'sktm' => ['SKTM (bila ada)', false], 'parent_consent' => ['Surat persetujuan orang tua/wali', true]];

    public const EDUCATIONS = ['SMA', 'SMK', 'MA', 'Paket C', 'SMP/MTs', 'Lainnya'];

    public const GENDERS = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    public const FAMILY_STATUSES = ['lengkap' => 'Orang tua lengkap', 'yatim' => 'Yatim', 'piatu' => 'Piatu', 'yatim_piatu' => 'Yatim piatu'];

    public const ECONOMIC_STATUSES = ['mampu' => 'Mampu', 'kurang_mampu' => 'Kurang mampu'];

    public const SOURCES = ['Media sosial', 'Teman / keluarga', 'Masjid / pengajian', 'Sekolah', 'Website', 'Lainnya'];

    /** Data pribadi tidak masuk activity log (PRD 10: tanpa PII di log). */
    protected array $logExcept = ['nik', 'birth_place', 'birth_date', 'address', 'village', 'phone', 'email', 'guardian_phone', 'father_name', 'mother_name',
        'guardian_name', 'family_status', 'economic_status', 'health_history', 'motivation', 'documents', 'history', 'scores', 'interview_note', 'home_visit_note', 'decision_note'];

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'birth_date' => 'date', 'documents' => 'array', 'scores' => 'array', 'history' => 'array', 'is_priority' => 'boolean',
        'interview_at' => 'datetime', 'home_visit_at' => 'datetime', 'decided_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL, true);
    }

    /** Tahap yang boleh dituju dari tahap sekarang (maju saja). */
    public function nextStages(): array
    {
        $index = array_search($this->status, self::STAGES, true);

        return $index === false ? [] : array_slice(self::STAGES, $index + 1);
    }

    public function canBeAccepted(): bool
    {
        return in_array($this->status, ['wawancara', 'kunjungan_rumah'], true);
    }

    public function averageScore(): ?float
    {
        $scores = array_filter((array) $this->scores, fn ($v) => $v !== null && $v !== '');

        return $scores ? round(array_sum($scores) / count($scores), 1) : null;
    }

    /** Prioritas (PRD 9.3): domisili kecamatan prioritas, yatim/piatu, atau kurang mampu. */
    public static function priorityFor(array $data): bool
    {
        $districts = array_map(fn ($d) => mb_strtolower(trim($d)), Setting::current()->priority_districts ?? []);

        return in_array(mb_strtolower(trim($data['district'] ?? '')), $districts, true)
            || in_array($data['family_status'] ?? null, ['yatim', 'piatu', 'yatim_piatu'], true)
            || ($data['economic_status'] ?? null) === 'kurang_mampu';
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function addHistory(string $status, ?string $note): void
    {
        $this->history = [...($this->history ?? []), ['status' => $status, 'note' => $note, 'by' => auth()->user()?->name, 'at' => now()->toDateTimeString()]];
    }
}
