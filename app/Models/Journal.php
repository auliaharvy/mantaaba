<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Voucher & jurnal (PRD 9.13.2). Tidak ada jurnal tanpa voucher, tidak ada voucher tanpa dokumen. */
class Journal extends Model
{
    use LogsActivity;

    public const TYPES = [
        'BPV' => 'Bank Payment Voucher', 'BRV' => 'Bank Receipt Voucher', 'CPV' => 'Cash Payment Voucher', 'CRV' => 'Cash Receipt Voucher',
        'RJV' => 'Receivable Journal Voucher', 'PJV' => 'Payable Journal Voucher', 'MJV' => 'Memo Journal Voucher', 'AJV' => 'Adjustment Journal Voucher',
    ];

    public const STATUSES = ['draft' => 'Draf', 'diajukan' => 'Diajukan', 'ditolak' => 'Ditolak', 'terposting' => 'Terposting', 'dibalik' => 'Dibalik'];

    public const STATUS_COLORS = ['draft' => 'light', 'diajukan' => 'light-warning', 'ditolak' => 'light-danger', 'terposting' => 'light-success', 'dibalik' => 'light-dark'];

    /** Status yang memengaruhi saldo. */
    public const POSTED = ['terposting', 'dibalik'];

    /** Voucher sistem yang diposting langsung pemegang period-close (aturan 12). */
    public const SELF_POSTED_SOURCES = ['opening_balance', 'year_close'];

    protected $fillable = ['voucher_type', 'date', 'fiscal_period_id', 'description', 'party_id', 'cost_center_id', 'reference_no', 'attachments',
        'status', 'is_system', 'source_type', 'source_id', 'reversal_of_id', 'created_by'];

    protected $casts = ['date' => 'date', 'attachments' => 'array', 'is_system' => 'boolean', 'submitted_at' => 'datetime', 'posted_at' => 'datetime'];

    protected $attributes = ['status' => 'draft'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class)->orderBy('id');
    }

    public function period()
    {
        return $this->belongsTo(FiscalPeriod::class, 'fiscal_period_id');
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'ditolak'], true);
    }

    public function total(): string
    {
        return number_format($this->lines->sum('debit'), 2, '.', '');
    }

    public function displayNumber(): string
    {
        return $this->number ?? "Draf #{$this->id}";
    }
}
