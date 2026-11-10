<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** Donasi (PRD 9.15). Nama donatur tidak pernah tampil publik. */
class Donation extends Model
{
    use FlushesSiteCache, LogsActivity;

    public const STATUSES = ['menunggu' => 'Menunggu verifikasi', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'];

    public const KINDS = ['transfer' => 'Transfer', 'tunai' => 'Tunai', 'barang' => 'Barang'];

    /** Peruntukan → [label, akun pendapatan]. Umum = tanpa pembatasan [TBD Q10]. */
    public const PURPOSES = [
        'umum' => ['Umum (operasional)', '4-1100'],
        'pendidikan' => ['Pendidikan', '4-1200'],
        'pangan' => ['Pangan', '4-1200'],
        'pembangunan' => ['Pembangunan', '4-1200'],
        'wakaf' => ['Wakaf', '4-1400'],
    ];

    protected array $logExcept = ['phone', 'email', 'ip'];

    protected $fillable = ['party_id', 'donor_name', 'is_anonymous', 'phone', 'email', 'kind', 'amount', 'transferred_at', 'coa_bank_account_id',
        'purpose', 'item_description', 'proof', 'message', 'status', 'reject_reason', 'ip'];

    protected $casts = ['amount' => 'decimal:2', 'transferred_at' => 'date', 'is_anonymous' => 'boolean', 'verified_at' => 'datetime'];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function account()
    {
        return $this->belongsTo(CoaAccount::class, 'coa_bank_account_id');
    }

    public function displayName(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }

    public function purposeLabel(): string
    {
        return self::PURPOSES[$this->purpose][0] ?? $this->purpose;
    }
}
