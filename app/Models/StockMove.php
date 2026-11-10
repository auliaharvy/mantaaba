<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mutasi stok. Log aktivitas hanya untuk opname, rusak, dan pembatalan (dicatat StockLedger, PRD 9.20). */
class StockMove extends Model
{
    /** tipe => [label, arah: 1 masuk, -1 keluar, 0 opname (bertanda)] */
    public const TYPES = [
        'masuk_beli' => ['Masuk (beli)', 1], 'masuk_panen' => ['Masuk (panen)', 1], 'masuk_donasi' => ['Masuk (donasi)', 1],
        'keluar_pakai' => ['Keluar (pakai)', -1], 'keluar_jual' => ['Keluar (jual)', -1], 'keluar_rusak' => ['Keluar (rusak/hilang)', -1],
        'transfer_keluar' => ['Transfer keluar', -1], 'transfer_masuk' => ['Transfer masuk', 1], 'opname' => ['Penyesuaian opname', 0],
    ];

    protected $fillable = ['date', 'item_id', 'warehouse_id', 'type', 'qty', 'unit_cost', 'total_cost', 'party_id', 'cost_center_id', 'farm_cycle_id',
        'source_type', 'source_id', 'journal_id', 'note', 'created_by'];

    protected $casts = ['date' => 'date', 'qty' => 'decimal:3', 'unit_cost' => 'decimal:4', 'total_cost' => 'decimal:2'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }
}
