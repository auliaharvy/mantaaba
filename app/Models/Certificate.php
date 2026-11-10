<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Sertifikat skema & kelulusan inkubasi (PRD 9.7). Dicabut tetap tercatat; terbit ulang = nomor baru. */
class Certificate extends Model
{
    use LogsActivity;

    public const INCUBATION = 'INK';

    protected $fillable = ['member_id', 'skema_result_id', 'series', 'year', 'sequence', 'number', 'verification_code', 'file', 'issued_at', 'revoked_at', 'revoke_reason'];

    protected $casts = ['issued_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function result()
    {
        return $this->belongsTo(SkemaResult::class, 'skema_result_id');
    }

    public function isValid(): bool
    {
        return $this->issued_at !== null && $this->revoked_at === null;
    }

    public function isIncubation(): bool
    {
        return $this->series === self::INCUBATION;
    }

    /** Judul program pada sertifikat. */
    public function title(): string
    {
        return $this->isIncubation() ? 'Kelulusan Inkubasi Karakter & Vokasi Bisnis' : 'Skema ' . ($this->result?->enrollment?->skema?->name ?? $this->series);
    }
}
