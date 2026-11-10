<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FiscalPeriod;
use App\Models\Item;
use App\Models\Journal;
use App\Models\StockMove;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Buku stok tunggal (PRD 9.10). Penilaian rata-rata tertimbang per barang (semua gudang):
 * masuk bernilai memperbarui avg_cost (panen = Rp 0), keluar memakai avg_cost saat itu.
 * Hard-stop: stok gudang tidak boleh minus; tanggal di periode tertutup atau yang jurnal pemakaiannya sudah dibuat ditolak.
 */
class StockLedger
{
    /** Saldo (qty) barang, opsional per gudang. */
    public static function balance(int $itemId, ?int $warehouseId = null): float
    {
        return round((float) StockMove::where('item_id', $itemId)->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))->sum('qty'), 3);
    }

    public static function ensureOpen(string $date): void
    {
        $period = FiscalPeriod::forDate($date);
        if ($period->isClosed()) {
            throw ValidationException::withMessages(['date' => "Periode {$period->label()} sudah ditutup."]);
        }
        if (Journal::where('source_type', 'monthly:stock')->where('source_id', $period->id)->whereNotIn('status', ['ditolak', 'dibalik'])->exists()) {
            throw ValidationException::withMessages(['date' => "Jurnal pemakaian persediaan {$period->label()} sudah dibuat; tolak/balik jurnal itu dulu untuk menambah mutasi."]);
        }
    }

    /**
     * Catat satu mutasi. $qty selalu positif kecuali tipe opname (bertanda).
     * $attrs: unit_cost (masuk_beli/masuk_donasi), party_id, cost_center_id, farm_cycle_id, journal_id, note.
     */
    public function record(string $type, string $date, Item|int $item, Warehouse|int $warehouse, float $qty, User $user, ?Model $source = null, array $attrs = []): StockMove
    {
        abort_unless(isset(StockMove::TYPES[$type]), 422, 'Tipe mutasi tidak dikenal.');
        if ($qty == 0 || ($type !== 'opname' && $qty < 0)) {
            throw ValidationException::withMessages(['qty' => 'Jumlah harus lebih dari 0.']);
        }
        self::ensureOpen($date);

        return DB::transaction(function () use ($type, $date, $item, $warehouse, $qty, $user, $source, $attrs) {
            $item = Item::whereKey($item instanceof Item ? $item->id : $item)->lockForUpdate()->firstOrFail();
            $warehouse = $warehouse instanceof Warehouse ? $warehouse : Warehouse::findOrFail($warehouse);
            $direction = StockMove::TYPES[$type][1];
            $signed = round($direction === 0 ? $qty : $direction * $qty, 3);

            if ($signed < 0) {
                $available = self::balance($item->id, $warehouse->id);
                if ($available + $signed < -0.0005) {
                    throw ValidationException::withMessages(['qty' => sprintf('Stok %s di %s tidak cukup: sisa %s %s, dibutuhkan %s %s.',
                        $item->name, $warehouse->name, self::qty($available), $item->unit, self::qty(-$signed), $item->unit)]);
                }
            }

            $unitCost = (float) $item->avg_cost;
            if (in_array($type, ['masuk_beli', 'masuk_donasi', 'masuk_panen'], true)) {
                $unitCost = $type === 'masuk_panen' ? 0.0 : (float) ($attrs['unit_cost'] ?? 0);
                $onHand = max(0, self::balance($item->id));
                $item->avg_cost = round(($onHand * (float) $item->avg_cost + $signed * $unitCost) / ($onHand + $signed), 4);
                $item->save();
            }

            $move = StockMove::create([
                'date' => $date, 'item_id' => $item->id, 'warehouse_id' => $warehouse->id, 'type' => $type, 'qty' => $signed,
                'unit_cost' => $unitCost, 'total_cost' => round($signed * $unitCost, 2),
                'party_id' => $attrs['party_id'] ?? null, 'cost_center_id' => $attrs['cost_center_id'] ?? $warehouse->cost_center_id,
                'farm_cycle_id' => $attrs['farm_cycle_id'] ?? null, 'journal_id' => $attrs['journal_id'] ?? null, 'note' => $attrs['note'] ?? null,
                'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(), 'created_by' => $user->id,
            ]);
            if (in_array($type, ['opname', 'keluar_rusak'], true)) {
                ActivityLog::record('created', $move, ['after' => $move->only(['date', 'item_id', 'warehouse_id', 'type', 'qty', 'total_cost', 'note'])]);
            }

            return $move;
        });
    }

    /** Pindah barang antargudang (nilai tetap avg_cost). */
    public function transfer(string $date, Item $item, Warehouse $from, Warehouse $to, float $qty, User $user, ?string $note = null): void
    {
        abort_if($from->is($to), 422, 'Gudang asal dan tujuan sama.');
        DB::transaction(function () use ($date, $item, $from, $to, $qty, $user, $note) {
            $this->record('transfer_keluar', $date, $item, $from, $qty, $user, null, ['note' => $note ?? "Ke {$to->name}"]);
            $this->record('transfer_masuk', $date, $item, $to, $qty, $user, null, ['note' => $note ?? "Dari {$from->name}"]);
        });
    }

    /**
     * Batalkan mutasi masuk (mis. pembelian dibatalkan): stok harus masih cukup; avg_cost dikembalikan.
     * shortcut: avg dihitung mundur dari nilai sekarang, tidak menilai ulang mutasi keluar yang terjadi di antaranya.
     */
    public function cancel(iterable $moves, User $user): void
    {
        DB::transaction(function () use ($moves, $user) {
            foreach ($moves as $move) {
                self::ensureOpen($move->date->toDateString());
                $item = Item::whereKey($move->item_id)->lockForUpdate()->first();
                $qty = (float) $move->qty;
                if ($qty > 0 && self::balance($item->id, $move->warehouse_id) - $qty < -0.0005) {
                    throw ValidationException::withMessages(['qty' => "Stok {$item->name} sudah terpakai; batalkan tidak bisa (sisa " . self::qty(self::balance($item->id, $move->warehouse_id)) . " {$item->unit})."]);
                }
                $onHand = self::balance($item->id);
                $rest = $onHand - $qty;
                if ($rest > 0.0005) {
                    $item->avg_cost = max(0, round(($onHand * (float) $item->avg_cost - (float) $move->total_cost) / $rest, 4));
                    $item->save();
                }
                ActivityLog::record('deleted', $move, ['before' => $move->only(['date', 'item_id', 'warehouse_id', 'type', 'qty', 'total_cost', 'source_type', 'source_id'])]);
                $move->delete();
            }
        });
    }

    public static function qty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    }
}
