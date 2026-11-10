<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmPlot extends Model
{
    public const TYPES = ['lahan' => 'Lahan', 'kolam' => 'Kolam', 'kandang' => 'Kandang'];

    public const STATUSES = ['kosong' => 'Kosong', 'aktif' => 'Aktif', 'istirahat' => 'Istirahat'];

    protected $fillable = ['type', 'code', 'name', 'size', 'size_unit', 'location_note', 'photo', 'status', 'pic_member_id'];

    public function cycles()
    {
        return $this->hasMany(FarmCycle::class, 'plot_id');
    }

    public function activeCycle()
    {
        return $this->hasOne(FarmCycle::class, 'plot_id')->whereIn('status', ['berjalan', 'panen'])->latestOfMany();
    }

    public function pic()
    {
        return $this->belongsTo(Member::class, 'pic_member_id');
    }

    /** Pusat biaya: kolam → KLM, lainnya → KBN. */
    public function costCenterCode(): string
    {
        return $this->type === 'kolam' ? 'KLM' : 'KBN';
    }
}
