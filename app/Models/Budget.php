<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Anggaran tahunan per akun & pusat biaya (PRD 9.13.5). */
class Budget extends Model
{
    protected $fillable = ['year', 'coa_account_id', 'cost_center_id', 'amount', 'monthly'];

    protected $casts = ['amount' => 'decimal:2', 'monthly' => 'array'];

    public function account()
    {
        return $this->belongsTo(CoaAccount::class, 'coa_account_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }
}
