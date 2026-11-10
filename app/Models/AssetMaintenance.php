<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    public const TYPES = ['servis' => 'Servis', 'perbaikan' => 'Perbaikan', 'pajak' => 'Pajak', 'stnk' => 'STNK', 'kir' => 'KIR', 'lainnya' => 'Lainnya'];

    protected $fillable = ['asset_id', 'date', 'type', 'description', 'cost', 'vendor_party_id', 'odometer', 'next_due_at', 'attachment', 'created_by'];

    protected $casts = ['date' => 'date', 'next_due_at' => 'date', 'cost' => 'decimal:2'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Party::class, 'vendor_party_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }
}
