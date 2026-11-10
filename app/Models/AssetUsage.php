<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetUsage extends Model
{
    protected $fillable = ['asset_id', 'user_id', 'purpose', 'destination', 'started_at', 'ended_at', 'odometer_start', 'odometer_end', 'fuel_cost', 'fuel_liters', 'receipt', 'note', 'status'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime', 'fuel_cost' => 'decimal:2', 'fuel_liters' => 'decimal:2'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function distance(): ?int
    {
        return $this->odometer_end !== null && $this->odometer_start !== null ? $this->odometer_end - $this->odometer_start : null;
    }
}
