<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Barang (PRD 9.10). Saldo = SUM(stock_moves.qty); nilai keluar memakai avg_cost (rata-rata tertimbang). */
class Item extends Model
{
    protected $fillable = ['code', 'name', 'category', 'unit', 'min_stock', 'avg_cost', 'inventory_account_id', 'expense_account_id', 'is_active'];

    protected $casts = ['min_stock' => 'decimal:3', 'avg_cost' => 'decimal:4', 'is_active' => 'boolean'];

    public function moves()
    {
        return $this->hasMany(StockMove::class);
    }

    public function inventoryAccount()
    {
        return $this->belongsTo(CoaAccount::class, 'inventory_account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(CoaAccount::class, 'expense_account_id');
    }

    public function categoryLabel(): string
    {
        return config("mantaaba.item_categories.{$this->category}.0", $this->category);
    }

    public function label(): string
    {
        return "{$this->name} ({$this->unit})";
    }

    /** Saldo per item [item_id => qty], opsional per gudang. */
    public static function balances(?int $warehouseId = null): \Illuminate\Support\Collection
    {
        return StockMove::when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->groupBy('item_id')->select('item_id', DB::raw('SUM(qty) as qty'))->pluck('qty', 'item_id')->map(fn ($q) => (float) $q);
    }

    /** Barang aktif dengan saldo total di bawah stok minimum. */
    public static function lowStock(): \Illuminate\Support\Collection
    {
        $balances = self::balances();

        return static::where('is_active', true)->where('min_stock', '>', 0)->orderBy('name')->get()
            ->filter(fn ($i) => ($balances[$i->id] ?? 0) < (float) $i->min_stock)
            ->each(fn ($i) => $i->setAttribute('balance', $balances[$i->id] ?? 0))->values();
    }

    public static function nextCode(string $category): string
    {
        $prefix = strtoupper(substr(str_replace('_', '', $category), 0, 3));
        $last = static::where('code', 'like', "{$prefix}-%")->max('code');

        return sprintf('%s-%03d', $prefix, $last ? (int) substr($last, 4) + 1 : 1);
    }
}
