<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\CostCenter;
use App\Models\FarmCycle;
use App\Models\FarmLog;
use App\Models\Harvest;
use App\Models\Item;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\StockMove;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Money;
use App\Support\PostingRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Kebun & kolam (PRD 9.11): log (pemakaian stok), panen (alur stok & voucher penjualan), tutup/buka ulang siklus, ringkasan. */
class FarmService
{
    public function __construct(private StockLedger $ledger, private JournalPoster $poster)
    {
    }

    public function log(FarmCycle $cycle, array $data, User $user): FarmLog
    {
        $this->ensureOpen($cycle);
        if ($data['activity'] === 'kematian' && empty($data['qty'])) {
            throw ValidationException::withMessages(['qty' => 'Isi jumlah yang mati.']);
        }

        return DB::transaction(function () use ($cycle, $data, $user) {
            $log = $cycle->logs()->create($data + ['created_by' => $user->id]);
            if ($log->item_id) {
                if (! $log->warehouse_id || ! $log->qty) {
                    throw ValidationException::withMessages(['qty' => 'Pemakaian barang wajib diisi jumlah dan gudang.']);
                }
                $this->ledger->record('keluar_pakai', $log->date->toDateString(), $log->item_id, $log->warehouse_id, (float) $log->qty, $user, $log, [
                    'cost_center_id' => $this->costCenter($cycle), 'farm_cycle_id' => $cycle->id, 'note' => FarmLog::ACTIVITIES[$log->activity] . ' · ' . $cycle->label(),
                ]);
            }

            return $log;
        });
    }

    /** Panen: masuk_panen (Rp 0) ke gudang; jual → keluar_jual + voucher penjualan; sedekah → keluar_pakai. */
    public function harvest(FarmCycle $cycle, array $data, User $user): Harvest
    {
        $this->ensureOpen($cycle);
        $item = Item::findOrFail($data['item_id']);
        abort_unless($item->category === 'hasil_panen', 422, 'Pilih barang berkategori hasil panen.');
        if ($data['destination'] === 'jual' && (empty($data['sale_price']) || empty($data['payment']))) {
            throw ValidationException::withMessages(['sale_price' => 'Panen dijual wajib diisi harga jual dan cara bayar.']);
        }
        if (($data['payment'] ?? null) === 'kredit' && empty($data['party_id'])) {
            throw ValidationException::withMessages(['party_id' => 'Penjualan kredit wajib memilih pembeli.']);
        }

        return DB::transaction(function () use ($cycle, $data, $item, $user) {
            $harvest = $cycle->harvests()->create($data + ['unit' => $item->unit, 'created_by' => $user->id]);
            $date = $harvest->date->toDateString();
            $warehouse = Warehouse::findOrFail($harvest->warehouse_id);
            $attrs = ['cost_center_id' => $this->costCenter($cycle), 'farm_cycle_id' => $cycle->id, 'note' => Harvest::DESTINATIONS[$harvest->destination] . ' · ' . $cycle->label()];
            $this->ledger->record('masuk_panen', $date, $item, $warehouse, (float) $harvest->qty, $user, $harvest, $attrs);
            if ($harvest->destination === 'jual') {
                $this->ledger->record('keluar_jual', $date, $item, $warehouse, (float) $harvest->qty, $user, $harvest, $attrs + ['party_id' => $harvest->party_id]);
                $harvest->forceFill(['journal_id' => $this->saleVoucher($cycle, $harvest, $user)->id])->save();
            } elseif ($harvest->destination === 'sedekah') {
                $this->ledger->record('keluar_pakai', $date, $item, $warehouse, (float) $harvest->qty, $user, $harvest, ['cost_center_id' => CostCenter::byCode('UMUM')->id] + $attrs);
            }
            if ($cycle->status === 'berjalan') {
                $cycle->forceFill(['status' => 'panen'])->save();
            }

            return $harvest;
        });
    }

    public function close(FarmCycle $cycle, string $status, ?string $reason, User $user): void
    {
        abort_unless(in_array($status, ['selesai', 'gagal'], true), 422);
        $this->ensureOpen($cycle);
        if ($status === 'gagal' && blank($reason)) {
            throw ValidationException::withMessages(['failure_reason' => 'Tuliskan penyebab gagal.']);
        }
        $cycle->forceFill(['status' => $status, 'closed_at' => now(), 'failure_reason' => $reason])->save();
        $cycle->plot->update(['status' => 'kosong']);
    }

    /** Buka ulang siklus terkunci (pemegang farm-update), tercatat di log aktivitas. */
    public function reopen(FarmCycle $cycle, string $reason, User $user): void
    {
        abort_unless($cycle->isLocked(), 422, 'Siklus belum ditutup.');
        abort_if($cycle->plot->activeCycle()->exists(), 422, 'Petak sudah dipakai siklus lain.');
        $cycle->forceFill(['status' => $cycle->harvests()->exists() ? 'panen' : 'berjalan', 'closed_at' => null])->save();
        $cycle->plot->update(['status' => 'aktif']);
        ActivityLog::record('farm_cycle_reopened', $cycle, ['reason' => $reason]);
    }

    /** Ringkasan siklus (PRD 9.11), uang dalam sen. */
    public static function summary(FarmCycle $cycle): array
    {
        $stockCost = -Money::cents(StockMove::where('farm_cycle_id', $cycle->id)->where('type', 'keluar_pakai')
            ->where(fn ($q) => $q->where('source_type', '!=', (new Harvest)->getMorphClass())->orWhereNull('source_type'))->sum('total_cost'));
        // beban lain yang ditandai siklus (mis. upah, listrik pompa); rekap bulanan persediaan tidak dihitung agar tidak dobel
        $lines = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')->join('coa_accounts', 'coa_accounts.id', '=', 'journal_lines.coa_account_id')
            ->where('journal_lines.farm_cycle_id', $cycle->id)->where('coa_accounts.type', 'beban')->whereIn('journals.status', Journal::POSTED)
            ->where(fn ($q) => $q->whereNull('journals.source_type')->orWhere('journals.source_type', '!=', 'monthly:stock'))
            ->selectRaw('COALESCE(SUM(journal_lines.debit),0) d, COALESCE(SUM(journal_lines.credit),0) c')->first();
        $journalCost = Money::cents($lines->d) - Money::cents($lines->c);
        $harvests = $cycle->harvests()->get();
        $qty = round((float) $harvests->sum('qty'), 3);
        $cost = $stockCost + $journalCost;
        $value = $harvests->sum(fn (Harvest $h) => $h->valueCents());
        $deaths = (float) $cycle->logs()->where('activity', 'kematian')->sum('qty');
        $initial = (float) $cycle->initial_qty;

        return [
            'stock_cost' => $stockCost, 'journal_cost' => $journalCost, 'cost' => $cost, 'harvest_qty' => $qty,
            'hpp' => $qty > 0 ? (int) round($cost / $qty) : null, 'value' => $value, 'surplus' => $value - $cost,
            'target_percent' => (float) $cycle->target_yield > 0 ? round($qty / (float) $cycle->target_yield * 100, 1) : null,
            'survival' => $cycle->plot->type === 'kolam' && $initial > 0 ? round(($initial - $deaths) / $initial * 100, 1) : null,
            'population' => $cycle->plot->type === 'kolam' && $initial > 0 ? $initial - $deaths : null,
        ];
    }

    private function saleVoucher(FarmCycle $cycle, Harvest $harvest, User $user): Journal
    {
        [$type, $rule] = ['tunai' => ['CRV', 'harvest_sale.cash'], 'transfer' => ['BRV', 'harvest_sale.bank'], 'kredit' => ['RJV', 'harvest_sale.receivable']][$harvest->payment];
        $amount = Money::decimal((int) round((float) $harvest->qty * Money::cents($harvest->sale_price)));

        return $this->poster->createSystem($type, $harvest->date->toDateString(), "Penjualan panen {$cycle->label()}: " . StockLedger::qty((float) $harvest->qty) . " {$harvest->unit}", [
            ['coa_account_id' => PostingRules::account($rule)->id, 'debit' => $amount, 'party_id' => $harvest->payment === 'kredit' ? $harvest->party_id : null],
            ['coa_account_id' => PostingRules::account('harvest_sale.revenue')->id, 'credit' => $amount, 'cost_center_id' => $this->costCenter($cycle), 'farm_cycle_id' => $cycle->id],
        ], $user, $harvest->getMorphClass(), $harvest->id, ['party_id' => $harvest->party_id]);
    }

    private function costCenter(FarmCycle $cycle): ?int
    {
        return CostCenter::byCode($cycle->plot->costCenterCode())?->id;
    }

    private function ensureOpen(FarmCycle $cycle): void
    {
        if ($cycle->isLocked()) {
            throw ValidationException::withMessages(['status' => 'Siklus sudah ' . FarmCycle::STATUSES[$cycle->status] . ' dan terkunci. Buka ulang dulu bila perlu koreksi.']);
        }
    }
}
