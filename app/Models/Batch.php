<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Angkatan (PRD 9.2). Hanya satu angkatan `open` pada satu waktu. */
class Batch extends Model
{
    use LogsActivity;

    public const STATUSES = ['draft' => 'Draf', 'open' => 'Pendaftaran dibuka', 'selection' => 'Seleksi', 'running' => 'Berjalan', 'done' => 'Selesai'];

    protected $fillable = ['code', 'name', 'admission_opens_at', 'admission_closes_at', 'starts_at', 'ends_at', 'quota', 'status', 'description', 'requirements_note'];

    protected $casts = ['admission_opens_at' => 'date', 'admission_closes_at' => 'date', 'starts_at' => 'date', 'ends_at' => 'date'];

    /** Angkatan yang sedang menerima pendaftaran: status open dan hari ini di dalam jendela pendaftaran. */
    public static function acceptingApplications(): ?self
    {
        $today = today();

        return static::where('status', 'open')
            ->where(fn ($q) => $q->whereNull('admission_opens_at')->orWhere('admission_opens_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('admission_closes_at')->orWhere('admission_closes_at', '>=', $today))
            ->first();
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function acceptedCount(): int
    {
        return $this->applicants()->where('status', 'diterima')->count();
    }
}
