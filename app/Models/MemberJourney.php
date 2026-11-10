<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Perjalanan anggota (PRD 9.4): fase, penempatan, jabatan, pendidikan formal, prestasi. */
class MemberJourney extends Model
{
    public const TYPES = ['fase' => 'Fase', 'penempatan' => 'Penempatan', 'jabatan' => 'Jabatan', 'pendidikan_formal' => 'Pendidikan formal', 'prestasi' => 'Prestasi', 'lainnya' => 'Lainnya'];

    public const ROLE_LEVELS = ['staf' => 'Staf', 'supervisor' => 'Supervisor', 'manajer' => 'Manajer', 'direktur' => 'Direktur'];

    protected $fillable = ['type', 'title', 'business_unit_id', 'role_level', 'started_at', 'ended_at', 'meta', 'notes'];

    protected $casts = ['started_at' => 'date', 'ended_at' => 'date', 'meta' => 'array'];

    protected static function booted(): void
    {
        // satu penempatan aktif per anggota: penempatan baru menutup yang lama
        static::creating(function (self $journey) {
            if ($journey->type === 'penempatan' && ! $journey->ended_at) {
                static::where('member_id', $journey->member_id)->where('type', 'penempatan')->whereNull('ended_at')->update(['ended_at' => $journey->started_at]);
            }
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
