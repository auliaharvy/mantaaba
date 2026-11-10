<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catatan kesehatan siswa (PRD 9.4), privat untuk pemegang health-view. */
class HealthRecord extends Model
{
    public const ACTIONS = ['istirahat' => 'Istirahat', 'obat' => 'Obat', 'puskesmas' => 'Puskesmas', 'klinik' => 'Klinik', 'rumah_sakit' => 'Rumah sakit'];

    protected $fillable = ['member_id', 'date', 'complaint', 'action', 'facility', 'cost', 'note', 'created_by'];

    protected $casts = ['date' => 'date', 'cost' => 'decimal:2'];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
