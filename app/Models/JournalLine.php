<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Baris jurnal: debit ATAU kredit (PRD 9.13.2). */
class JournalLine extends Model
{
    protected $fillable = ['journal_id', 'coa_account_id', 'debit', 'credit', 'cost_center_id', 'party_id', 'farm_cycle_id', 'asset_id', 'memo'];

    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(CoaAccount::class, 'coa_account_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }
}
