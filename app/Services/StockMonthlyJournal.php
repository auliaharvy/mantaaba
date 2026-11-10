<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\StockMove;
use App\Models\User;
use App\Support\Money;
use App\Support\PostingRules;
use Illuminate\Support\Carbon;

/**
 * Rekap bulanan persediaan (PRD 9.16), satu AJV per periode:
 * pemakaian/jual → Dr beban barang (pusat biaya & siklus kebun) · Cr persediaan;
 * rusak & opname kurang → Dr beban lain-lain; opname lebih → Cr pendapatan lain.
 */
class StockMonthlyJournal
{
    public const LABEL = 'Pemakaian & penyesuaian persediaan';

    public function generate(FiscalPeriod $period, User $user): ?Journal
    {
        $from = Carbon::create($period->year, $period->month)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $moves = StockMove::with('item')->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('type', ['keluar_pakai', 'keluar_jual', 'keluar_rusak', 'opname'])->where('total_cost', '!=', 0)->get();
        if ($moves->isEmpty()) {
            return null;
        }
        $umum = CostCenter::byCode('UMUM')->id;
        $loss = PostingRules::account('stock_loss.debit')->id;
        $gain = PostingRules::account('stock_gain.credit')->id;
        $lines = [];
        $add = function (int $account, int $cents, ?int $center = null, ?int $cycle = null) use (&$lines) {
            $key = "{$account}-{$center}-{$cycle}";
            $lines[$key] = ['coa_account_id' => $account, 'cost_center_id' => $center, 'farm_cycle_id' => $cycle, 'cents' => ($lines[$key]['cents'] ?? 0) + $cents];
        };
        foreach ($moves as $move) {
            $value = Money::cents($move->total_cost); // keluar negatif, opname lebih positif
            $add($move->item->inventory_account_id, $value);
            match (true) {
                in_array($move->type, ['keluar_pakai', 'keluar_jual'], true) => $add($move->item->expense_account_id, -$value, $move->cost_center_id ?? $umum, $move->farm_cycle_id),
                $value < 0 => $add($loss, -$value, $umum),
                default => $add($gain, -$value, $umum),
            };
        }
        $journalLines = collect($lines)->filter(fn ($l) => $l['cents'] !== 0)->map(fn ($l) => [
            'coa_account_id' => $l['coa_account_id'], 'cost_center_id' => $l['cost_center_id'], 'farm_cycle_id' => $l['farm_cycle_id'],
            $l['cents'] > 0 ? 'debit' : 'credit' => Money::decimal(abs($l['cents'])),
        ])->values()->all();
        if (count($journalLines) < 2) {
            return null;
        }

        return app(JournalPoster::class)->createSystem('AJV', $to->toDateString(), "Rekap pemakaian & penyesuaian persediaan {$period->label()}", $journalLines, $user, 'monthly:stock', $period->id);
    }
}
