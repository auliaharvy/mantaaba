<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    public const DESTINATIONS = ['dapur' => 'Ke dapur', 'simpan' => 'Disimpan', 'jual' => 'Dijual', 'sedekah' => 'Sedekah'];

    public const PAYMENTS = ['tunai' => 'Tunai', 'transfer' => 'Transfer', 'kredit' => 'Kredit (piutang)'];

    protected $fillable = ['cycle_id', 'date', 'qty', 'unit', 'grade', 'item_id', 'destination', 'warehouse_id', 'market_price', 'sale_price', 'party_id', 'payment', 'created_by'];

    protected $casts = ['date' => 'date', 'qty' => 'decimal:3', 'market_price' => 'decimal:2', 'sale_price' => 'decimal:2'];

    public function cycle()
    {
        return $this->belongsTo(FarmCycle::class, 'cycle_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Nilai hasil (sen): qty × (harga jual ?? harga pasar). */
    public function valueCents(): int
    {
        return (int) round((float) $this->qty * \App\Support\Money::cents($this->sale_price ?? $this->market_price));
    }
}
