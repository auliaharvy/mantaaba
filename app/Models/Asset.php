<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Aset tetap (PRD 9.17). Penyusutan garis lurus bulanan; nilai dalam sen pada method hitung. */
class Asset extends Model
{
    use LogsActivity;

    public const CONDITIONS = ['baik' => 'Baik', 'rusak_ringan' => 'Rusak ringan', 'rusak_berat' => 'Rusak berat', 'hilang' => 'Hilang', 'dilepas' => 'Dilepas'];

    public const SOURCES = ['beli' => 'Beli', 'hibah' => 'Hibah', 'wakaf' => 'Wakaf'];

    protected $logExcept = ['documents'];

    protected $fillable = ['code', 'name', 'category', 'location', 'condition', 'pic_user_id', 'acquired_at', 'acquisition_value', 'source', 'useful_life_months',
        'residual_value', 'depreciation_start', 'opening_accumulated', 'asset_account_id', 'accumulated_account_id', 'expense_account_id', 'photo', 'documents', 'notes'];

    protected $casts = ['acquired_at' => 'date', 'depreciation_start' => 'date', 'disposed_at' => 'date', 'documents' => 'array',
        'acquisition_value' => 'decimal:2', 'residual_value' => 'decimal:2', 'opening_accumulated' => 'decimal:2'];

    public function vehicle()
    {
        return $this->hasOne(AssetVehicle::class);
    }

    public function usages()
    {
        return $this->hasMany(AssetUsage::class)->latest('started_at');
    }

    public function maintenances()
    {
        return $this->hasMany(AssetMaintenance::class)->latest('date')->latest('id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function disposalJournal()
    {
        return $this->belongsTo(Journal::class, 'disposal_journal_id');
    }

    public function assetAccount()
    {
        return $this->belongsTo(CoaAccount::class, 'asset_account_id');
    }

    public function categoryLabel(): string
    {
        return config("mantaaba.asset_categories.{$this->category}.0", $this->category);
    }

    public function isVehicle(): bool
    {
        return $this->category === 'kendaraan';
    }

    public function isDepreciable(): bool
    {
        return $this->category !== 'tanah' && (int) $this->useful_life_months > 0 && $this->accumulated_account_id && $this->depreciation_start;
    }

    public function depreciableCents(): int
    {
        return max(0, Money::cents($this->acquisition_value) - Money::cents($this->residual_value));
    }

    /** Akumulasi penyusutan (sen): saldo awal + jurnal bertanda aset di akun akumulasi. $pending: ikut voucher diajukan/draf. */
    public function accumulatedCents(bool $pending = false): int
    {
        $row = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.asset_id', $this->id)->where('journal_lines.coa_account_id', $this->accumulated_account_id)
            ->whereIn('journals.status', $pending ? ['draft', 'diajukan', 'terposting', 'dibalik'] : Journal::POSTED)
            ->selectRaw('COALESCE(SUM(journal_lines.credit),0) c, COALESCE(SUM(journal_lines.debit),0) d')->first();

        return Money::cents($this->opening_accumulated) + Money::cents($row->c) - Money::cents($row->d);
    }

    public function bookValueCents(): int
    {
        return $this->disposed_at ? 0 : Money::cents($this->acquisition_value) - ($this->accumulated_account_id ? $this->accumulatedCents() : 0);
    }

    /** Akumulasi yang seharusnya sampai akhir bulan $date (garis lurus, tidak melebihi nilai tersusutkan). */
    public function expectedAccumulatedCents(Carbon|string $date): int
    {
        if (! $this->isDepreciable()) {
            return 0;
        }
        $end = Carbon::parse($date)->endOfMonth();
        $start = $this->depreciation_start->copy()->startOfMonth();
        if ($end->lt($start)) {
            return 0;
        }
        $months = ($end->year - $start->year) * 12 + $end->month - $start->month + 1;
        $life = (int) $this->useful_life_months;

        return $months >= $life ? $this->depreciableCents() : intdiv($this->depreciableCents() * $months, $life);
    }

    /** Jadwal penyusutan per bulan: [bulan, beban, akumulasi]. */
    public function schedule(): array
    {
        if (! $this->isDepreciable()) {
            return [];
        }
        $rows = [];
        $previous = 0;
        for ($i = 0; $i < (int) $this->useful_life_months; $i++) {
            $month = $this->depreciation_start->copy()->startOfMonth()->addMonths($i);
            $cumulative = $this->expectedAccumulatedCents($month);
            $rows[] = ['month' => $month, 'amount' => $cumulative - $previous, 'cumulative' => $cumulative];
            $previous = $cumulative;
        }

        return $rows;
    }

    public static function nextCode(string $category): string
    {
        $prefix = 'AST-' . config("mantaaba.asset_categories.{$category}.1", 'LNY') . '-';
        $last = static::where('code', 'like', "{$prefix}%")->max('code');

        return $prefix . sprintf('%03d', $last ? (int) substr($last, -3) + 1 : 1);
    }
}
