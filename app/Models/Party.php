<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pihak: donatur, pemasok, pelanggan, anggota, lembaga (PRD 9.13.1). */
class Party extends Model
{
    public const TYPES = ['donatur' => 'Donatur', 'pemasok' => 'Pemasok', 'pelanggan' => 'Pelanggan', 'anggota' => 'Anggota', 'lembaga' => 'Lembaga', 'lainnya' => 'Lainnya'];

    protected $fillable = ['code', 'name', 'type', 'phone', 'email', 'address', 'npwp', 'member_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $attributes = ['is_active' => true];

    protected static function booted(): void
    {
        static::creating(function (Party $party) {
            if (blank($party->code)) {
                $prefix = strtoupper(substr($party->type, 0, 3));
                $last = static::where('code', 'like', "{$prefix}-%")->max('code');
                $party->code = sprintf('%s-%04d', $prefix, $last ? (int) substr($last, 4) + 1 : 1);
            }
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
