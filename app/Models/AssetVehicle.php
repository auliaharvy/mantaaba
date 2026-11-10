<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetVehicle extends Model
{
    protected $primaryKey = 'asset_id';

    public $incrementing = false;

    protected $fillable = ['asset_id', 'plate_number', 'brand_type', 'year', 'color', 'stnk_due_at', 'tax_due_at', 'kir_due_at', 'last_odometer',
        'service_interval_km', 'service_interval_days', 'last_service_at', 'last_service_km'];

    protected $casts = ['stnk_due_at' => 'date', 'tax_due_at' => 'date', 'kir_due_at' => 'date', 'last_service_at' => 'date'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /** Servis berikutnya: km & tanggal (null bila interval tidak diisi). */
    public function nextService(): array
    {
        return [
            'km' => $this->service_interval_km ? ($this->last_service_km ?? 0) + $this->service_interval_km : null,
            'date' => $this->service_interval_days && $this->last_service_at ? $this->last_service_at->copy()->addDays($this->service_interval_days) : null,
        ];
    }
}
