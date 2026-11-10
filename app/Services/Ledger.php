<?php

namespace App\Services;

use App\Models\CoaAccount;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Saldo & buku besar dari jurnal terposting, dengan agregasi SQL (PRD 10). */
class Ledger
{
    /** Saldo (debit − kredit) dalam sen sampai tanggal (inklusif). */
    public static function balanceCents(int $accountId, string $until): int
    {
        $row = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.coa_account_id', $accountId)->whereIn('journals.status', Journal::POSTED)->where('journals.date', '<=', $until)
            ->selectRaw('COALESCE(SUM(journal_lines.debit), 0) as d, COALESCE(SUM(journal_lines.credit), 0) as c')->first();

        return Money::cents($row->d) - Money::cents($row->c);
    }

    /** Neraca saldo per akun sampai tanggal: [account, debit, credit] dengan saldo di sisi normal. */
    public static function trialBalance(string $until): Collection
    {
        $sums = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereIn('journals.status', Journal::POSTED)->where('journals.date', '<=', $until)
            ->groupBy('journal_lines.coa_account_id')
            ->select('journal_lines.coa_account_id', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->get()->keyBy('coa_account_id');

        return CoaAccount::where('is_header', false)->orderBy('code')->get()->map(function (CoaAccount $account) use ($sums) {
            $net = Money::cents($sums[$account->id]->d ?? 0) - Money::cents($sums[$account->id]->c ?? 0);

            return ['account' => $account, 'debit' => $net > 0 ? $net : 0, 'credit' => $net < 0 ? -$net : 0];
        })->filter(fn ($row) => $row['debit'] || $row['credit'] || $row['account']->is_active)->values();
    }

    /** Mutasi akun dalam rentang + saldo awal & berjalan (dalam sen). */
    public static function ledger(CoaAccount $account, string $from, string $to): array
    {
        $opening = self::balanceCents($account->id, date('Y-m-d', strtotime($from . ' -1 day')));
        $running = $opening;
        $rows = JournalLine::with('journal:id,number,date,description,voucher_type,status', 'costCenter:id,code', 'party:id,name')
            ->where('coa_account_id', $account->id)
            ->whereHas('journal', fn ($q) => $q->whereIn('status', Journal::POSTED)->whereBetween('date', [$from, $to]))
            ->get()->sortBy(fn ($l) => [$l->journal->date->toDateString(), $l->journal->number])->values()
            ->map(function (JournalLine $line) use (&$running) {
                $running += Money::cents($line->debit) - Money::cents($line->credit);

                return ['line' => $line, 'balance' => $running];
            });

        return ['opening' => $opening, 'rows' => $rows, 'closing' => $running];
    }
}
