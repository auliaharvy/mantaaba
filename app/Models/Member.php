<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Anggota (PRD 9.4): siswa, alumni, pengurus, relawan, mitra, donatur. */
class Member extends Model
{
    use FlushesSiteCache, LogsActivity, SoftDeletes; // blok situs `members` (show_on_site)

    public const CATEGORIES = ['siswa' => 'Siswa', 'alumni' => 'Alumni', 'pengurus' => 'Pengurus', 'relawan' => 'Relawan', 'mitra' => 'Mitra', 'donatur' => 'Donatur'];

    public const STATUSES = ['aktif' => 'Aktif', 'cuti' => 'Cuti', 'mengundurkan_diri' => 'Mengundurkan diri', 'dikeluarkan' => 'Dikeluarkan', 'lulus' => 'Lulus'];

    /** Status yang mengakhiri keanggotaan: left_at terisi, akun nonaktif. */
    public const LEAVING = ['mengundurkan_diri', 'dikeluarkan'];

    public const PHASES = ['inkubasi' => 'Inkubasi', 'magang' => 'Magang', 'bekerja' => 'Bekerja', 'ownership' => 'Ownership', 'alumni' => 'Alumni'];

    /** Kolom privat: hanya untuk pemegang member-private (AC 9.4). */
    public const PRIVATE = ['nik', 'birth_place', 'birth_date', 'gender', 'address', 'village', 'district', 'regency', 'phone',
        'father_name', 'mother_name', 'guardian_name', 'guardian_phone', 'family_status', 'economic_status'];

    protected array $logExcept = [...self::PRIVATE, 'documents'];

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['birth_date' => 'date', 'joined_at' => 'date', 'left_at' => 'date', 'integrity_pact_signed_at' => 'datetime',
        'show_on_site' => 'boolean', 'documents' => 'array'];

    /** Setiap anggota otomatis punya Pihak (kode = nomor anggota) untuk sub-akun titipan/piutang, mis. 21500000-MTB-20260001. */
    protected static function booted(): void
    {
        static::created(fn (Member $member) => Party::create(['code' => $member->member_number, 'name' => $member->name, 'type' => 'anggota', 'member_id' => $member->id]));
        static::updated(function (Member $member) {
            if ($member->wasChanged('name')) {
                Party::where('member_id', $member->id)->update(['name' => $member->name]);
            }
        });
    }

    public function party()
    {
        return $this->hasOne(Party::class);
    }

    /** Nomor MTB-{tahun}{urut 4 digit}; panggil di dalam transaksi. */
    public static function nextNumber(int $year): string
    {
        $last = DB::table('members')->where('member_number', 'like', "MTB-{$year}%")->lockForUpdate()->max('member_number');

        return sprintf('MTB-%d%04d', $year, $last ? ((int) substr($last, 8)) + 1 : 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function journeys()
    {
        return $this->hasMany(MemberJourney::class)->orderByDesc('started_at')->orderByDesc('id');
    }

    public function healthRecords()
    {
        return $this->hasMany(HealthRecord::class)->latest('date');
    }

    public function coachingNotes()
    {
        return $this->hasMany(CoachingNote::class)->latest('date')->latest('id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function duties()
    {
        return $this->hasMany(Duty::class);
    }

    public function enrollments()
    {
        return $this->hasMany(SkemaEnrollment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class)->latest('issued_at');
    }

    /** Kelima skema lulus → siap dikukuhkan lulus inkubasi (PRD 9.7). */
    public function passedAllSkemas(): bool
    {
        $total = Skema::count();

        return $total > 0 && $this->enrollments()->where('status', 'lulus')->count() === $total;
    }

    public function activePlacement()
    {
        return $this->hasOne(MemberJourney::class)->where('type', 'penempatan')->whereNull('ended_at')->latestOfMany('started_at');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    public function needsIntegrityPact(): bool
    {
        return $this->category === 'siswa' && $this->status === 'aktif' && ! $this->integrity_pact_signed_at;
    }

    /** PRD 9.5: 3 pelanggaran sedang atau 1 berat dalam 30 hari. */
    public function hasViolationAlert(): bool
    {
        $recent = $this->coachingNotes()->where('date', '>=', today()->subDays(30))->pluck('type');

        return $recent->contains('pelanggaran_berat') || $recent->filter(fn ($t) => $t === 'pelanggaran_sedang')->count() >= 3;
    }

    /** Status baru beserta efeknya (PRD 9.4). */
    public function changeStatus(string $status, string $reason): void
    {
        $data = ['status' => $status];
        if (in_array($status, self::LEAVING, true)) {
            // PRD 9.4: keluar kamar & dari jadwal piket mendatang
            $data += ['left_at' => today(), 'left_reason' => $reason, 'room_id' => null];
        } elseif ($status === 'lulus') {
            $data += ['category' => 'alumni', 'phase' => 'alumni', 'left_at' => null, 'left_reason' => $reason];
        } else {
            $data += ['left_at' => null, 'left_reason' => $status === 'aktif' ? null : $reason];
        }
        DB::transaction(function () use ($data, $status) {
            $this->update($data);
            $this->user?->update(['is_active' => ! in_array($status, self::LEAVING, true)]);
            if (in_array($status, self::LEAVING, true)) {
                $this->duties()->where('status', 'terjadwal')->where('date', '>=', today())->delete();
            }
        });
    }

    /** Mulai fase baru: tutup perjalanan fase sebelumnya. */
    public function startPhase(string $phase): void
    {
        $this->journeys()->where('type', 'fase')->whereNull('ended_at')->update(['ended_at' => today()]);
        $this->journeys()->create(['type' => 'fase', 'title' => 'Fase ' . self::PHASES[$phase], 'started_at' => today()]);
    }
}
