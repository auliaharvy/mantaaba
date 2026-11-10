<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;

/** Rekonsiliasi kas fisik / rekening koran saat tutup bulan (PRD 9.13.4). */
class CashReconciliation extends Model
{
    protected $fillable = ['fiscal_period_id', 'coa_account_id', 'book_balance', 'actual_balance', 'note', 'created_by'];

    protected $casts = ['book_balance' => 'decimal:2', 'actual_balance' => 'decimal:2'];

    public function account()
    {
        return $this->belongsTo(CoaAccount::class, 'coa_account_id');
    }

    public function differenceCents(): int
    {
        return Money::cents($this->actual_balance) - Money::cents($this->book_balance);
    }
}
