<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;

/**
 * Penyusutan garis lurus bulanan (PRD 9.17, 9.16): satu AJV rekap per periode.
 * Beban per aset = akumulasi seharusnya s.d. akhir bulan − akumulasi tercatat (termasuk voucher diajukan), sehingga tidak pernah melebihi nilai tersusutkan.
 */
class DepreciationJournal
{
    public const LABEL = 'Penyusutan aset tetap';

    public function generate(FiscalPeriod $period, User $user): ?Journal
    {
        $end = Carbon::create($period->year, $period->month)->endOfMonth();
        $lines = [];
        $assets = Asset::where('category', '!=', 'tanah')->where('useful_life_months', '>', 0)->whereNotNull('accumulated_account_id')
            ->where('depreciation_start', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('disposed_at')->orWhere('disposed_at', '>', $end->toDateString()))->get();
        foreach ($assets as $asset) {
            $amount = $asset->expectedAccumulatedCents($end) - $asset->accumulatedCents(true);
            if ($amount <= 0) {
                continue;
            }
            $value = Money::decimal($amount);
            $lines[] = ['coa_account_id' => $asset->expense_account_id, 'debit' => $value, 'cost_center_id' => AssetService::costCenterId($asset), 'asset_id' => $asset->id, 'memo' => $asset->code];
            $lines[] = ['coa_account_id' => $asset->accumulated_account_id, 'credit' => $value, 'asset_id' => $asset->id, 'memo' => $asset->code];
        }
        if (! $lines) {
            return null;
        }

        return app(JournalPoster::class)->createSystem('AJV', $end->toDateString(), "Penyusutan aset tetap {$period->label()}", $lines, $user, 'monthly:depreciation', $period->id);
    }
}
