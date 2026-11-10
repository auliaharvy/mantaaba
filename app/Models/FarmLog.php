<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarmLog extends Model
{
    public const ACTIVITIES = ['olah_lahan' => 'Olah lahan', 'tanam' => 'Tanam', 'tebar' => 'Tebar', 'siram' => 'Siram', 'pupuk' => 'Pupuk', 'pakan' => 'Pakan',
        'semprot' => 'Semprot', 'sortir' => 'Sortir', 'kematian' => 'Kematian', 'hama_penyakit' => 'Hama/penyakit', 'pengukuran' => 'Pengukuran', 'lainnya' => 'Lainnya'];

    protected $fillable = ['cycle_id', 'date', 'activity', 'qty', 'unit', 'item_id', 'warehouse_id', 'description', 'photo', 'created_by'];

    protected $casts = ['date' => 'date', 'qty' => 'decimal:3'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockMove()
    {
        return $this->morphOne(StockMove::class, 'source');
    }
}
