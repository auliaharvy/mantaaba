<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Akun bagan akun (PRD 9.13.1). Akun header tidak bisa diposting. */
class CoaAccount extends Model
{
    use LogsActivity;

    public const TYPES = ['aset' => 'Aset', 'liabilitas' => 'Liabilitas', 'aset_neto' => 'Aset Neto', 'pendapatan' => 'Pendapatan', 'beban' => 'Beban'];

    public const RESTRICTIONS = ['tanpa_pembatasan' => 'Tanpa pembatasan', 'dengan_pembatasan' => 'Dengan pembatasan'];

    public const CASH_FLOW_GROUPS = ['operasi' => 'Operasi', 'investasi' => 'Investasi', 'pendanaan' => 'Pendanaan'];

    protected $fillable = ['code', 'name', 'type', 'normal_balance', 'parent_id', 'is_header', 'is_cash', 'is_bank', 'is_receivable', 'is_payable', 'is_contra',
        'restriction', 'cash_flow_group', 'statement_group', 'is_active', 'sort_order'];

    protected $casts = ['is_header' => 'boolean', 'is_cash' => 'boolean', 'is_bank' => 'boolean', 'is_receivable' => 'boolean', 'is_payable' => 'boolean',
        'is_contra' => 'boolean', 'is_active' => 'boolean', 'tax_mapping' => 'array'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isPostable(): bool
    {
        return ! $this->is_header && $this->is_active;
    }

    public function isCashOrBank(): bool
    {
        return $this->is_cash || $this->is_bank;
    }

    /** Pendapatan & beban wajib pusat biaya (aturan 9). */
    public function needsCostCenter(): bool
    {
        return in_array($this->type, ['pendapatan', 'beban'], true);
    }

    public function label(): string
    {
        return "{$this->code} {$this->name}";
    }

    public static function postable()
    {
        return static::where('is_header', false)->where('is_active', true)->orderBy('code');
    }

    public static function byCode(string $code): self
    {
        return static::where('code', $code)->firstOrFail();
    }
}
