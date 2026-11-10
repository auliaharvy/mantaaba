<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Member;
use App\Models\Party;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Laporan keuangan ISAK 35 (PRD 9.14) dari jurnal terposting. Semua angka dalam sen (int) dan dihitung dengan agregasi SQL.
 * Saldo "alami" = debit − kredit; dibalik untuk akun bersaldo normal kredit agar tampil positif.
 */
class FinancialReports
{
    /** Mutasi debit−kredit per akun pada rentang tanggal (from null = sejak awal). $operating: tanpa jurnal penutupan tahun. */
    public static function movements(?string $from, string $to, ?int $costCenterId = null, bool $operating = false): Collection
    {
        return JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->whereIn('journals.status', Journal::POSTED)->where('journals.date', '<=', $to)
            ->when($operating, fn ($q) => self::withoutYearClose($q))
            ->when($from, fn ($q) => $q->where('journals.date', '>=', $from))
            ->when($costCenterId, fn ($q) => $q->where('journal_lines.cost_center_id', $costCenterId))
            ->groupBy('journal_lines.coa_account_id')
            ->select('journal_lines.coa_account_id', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->get()->mapWithKeys(fn ($r) => [$r->coa_account_id => Money::cents($r->d) - Money::cents($r->c)]);
    }

    /** Jurnal penutupan tahun memindahkan saldo ke aset neto; laporan surplus/defisit, pusat biaya & anggaran tidak menghitungnya. */
    private static function withoutYearClose($query)
    {
        return $query->where(fn ($q) => $q->whereNull('journals.source_type')->orWhere('journals.source_type', '!=', 'year_close'));
    }

    /** Laporan Posisi Keuangan per tanggal. Aset = Liabilitas + Aset Neto (termasuk surplus berjalan). */
    public static function position(string $date): array
    {
        $net = self::movements(null, $date);
        $accounts = CoaAccount::where('is_header', false)->orderBy('code')->get();
        $row = fn (CoaAccount $a, int $sign) => ['account' => $a, 'amount' => $sign * ($net[$a->id] ?? 0)];

        $current = $accounts->filter(fn ($a) => $a->type === 'aset' && str_starts_with($a->code, '1-1'))->map(fn ($a) => $row($a, 1))->values();
        $nonCurrent = $accounts->filter(fn ($a) => $a->type === 'aset' && ! str_starts_with($a->code, '1-1'))->map(fn ($a) => $row($a, 1))->values();
        $liabilities = $accounts->where('type', 'liabilitas')->map(fn ($a) => $row($a, -1))->values();
        $surplus = self::surplusByRestriction(null, $date);
        $unrestricted = $accounts->filter(fn ($a) => $a->type === 'aset_neto' && $a->restriction !== 'dengan_pembatasan')->map(fn ($a) => $row($a, -1))->values();
        $restricted = $accounts->filter(fn ($a) => $a->type === 'aset_neto' && $a->restriction === 'dengan_pembatasan')->map(fn ($a) => $row($a, -1))->values();

        $totals = [
            'current' => $current->sum('amount'), 'non_current' => $nonCurrent->sum('amount'),
            'liabilities' => $liabilities->sum('amount'),
            'unrestricted' => $unrestricted->sum('amount') + $surplus['tanpa'],
            'restricted' => $restricted->sum('amount') + $surplus['dengan'],
        ];
        $totals['assets'] = $totals['current'] + $totals['non_current'];
        $totals['net_assets'] = $totals['unrestricted'] + $totals['restricted'];
        $totals['liabilities_net_assets'] = $totals['liabilities'] + $totals['net_assets'];

        return compact('current', 'nonCurrent', 'liabilities', 'unrestricted', 'restricted', 'surplus', 'totals') + ['date' => $date];
    }

    /** Surplus (pendapatan − beban) yang belum ditutup ke aset neto, per kelompok pembatasan. */
    public static function surplusByRestriction(?string $from, string $to): array
    {
        $net = self::movements($from, $to);
        $tanpa = 0;
        $dengan = 0;
        foreach (CoaAccount::whereIn('type', ['pendapatan', 'beban'])->where('is_header', false)->get() as $a) {
            $amount = -($net[$a->id] ?? 0); // pendapatan +, beban −
            $a->restriction === 'dengan_pembatasan' ? $dengan += $amount : $tanpa += $amount;
        }

        return ['tanpa' => $tanpa, 'dengan' => $dengan];
    }

    /** Laporan Penghasilan Komprehensif: kolom tanpa/dengan pembatasan. */
    public static function activities(string $from, string $to, ?int $costCenterId = null): array
    {
        $net = self::movements($from, $to, $costCenterId, true);
        $accounts = CoaAccount::whereIn('type', ['pendapatan', 'beban'])->where('is_header', false)->orderBy('code')->get();
        $revenues = $accounts->where('type', 'pendapatan')->map(fn ($a) => ['account' => $a, 'amount' => -($net[$a->id] ?? 0), 'restricted' => $a->restriction === 'dengan_pembatasan'])
            ->filter(fn ($r) => $r['amount'] !== 0 || $r['account']->is_active)->values();
        $expenses = $accounts->where('type', 'beban')->map(fn ($a) => ['account' => $a, 'amount' => $net[$a->id] ?? 0])
            ->filter(fn ($r) => $r['amount'] !== 0 || $r['account']->is_active)->values();
        // aset neto dibebaskan: tampil + di kolom tanpa pembatasan dan − di kolom dengan pembatasan
        $released = $revenues->first(fn ($r) => $r['account']->code === '4-9000')['amount'] ?? 0;
        $revenueTanpa = $revenues->reject(fn ($r) => $r['restricted'])->sum('amount');
        $revenueDengan = $revenues->filter(fn ($r) => $r['restricted'])->sum('amount');
        $expenseTotal = $expenses->sum('amount');

        return compact('revenues', 'expenses', 'released', 'revenueTanpa', 'revenueDengan', 'expenseTotal') + [
            'surplusTanpa' => $revenueTanpa - $expenseTotal,
            'surplusDengan' => $revenueDengan - $released,
            'from' => $from, 'to' => $to,
        ];
    }

    /** Laporan Perubahan Aset Neto per kelompok pembatasan. */
    public static function netAssetChanges(string $from, string $to): array
    {
        $before = Carbon::parse($from)->subDay()->toDateString();
        $open = self::position($before)['totals'];
        $close = self::position($to)['totals'];
        $activity = self::activities($from, $to);
        $row = fn (string $key, int $opening, int $closing, int $surplus) => ['opening' => $opening, 'surplus' => $surplus, 'other' => $closing - $opening - $surplus, 'closing' => $closing];

        return [
            'tanpa' => $row('tanpa', $open['unrestricted'], $close['unrestricted'], $activity['surplusTanpa']),
            'dengan' => $row('dengan', $open['restricted'], $close['restricted'], $activity['surplusDengan']),
            'from' => $from, 'to' => $to,
        ];
    }

    /**
     * Arus kas metode langsung: untuk tiap voucher yang menyentuh kas/bank, setiap baris non-kas menyumbang −(debit−kredit)
     * ke kelompok arus kas akunnya. Saldo awal kas + arus bersih = saldo akhir kas.
     */
    public static function cashFlow(string $from, string $to): array
    {
        $cashIds = CoaAccount::where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->pluck('id');
        $inRange = fn ($q) => $q->whereIn('status', Journal::POSTED)->whereBetween('date', [$from, $to]);
        // saldo awal pembukuan bukan arus kas: masuk ke kas awal periode
        $openingIds = Journal::where($inRange)->where('source_type', 'opening_balance')->pluck('id');
        $journalIds = JournalLine::whereIn('coa_account_id', $cashIds)->whereNotIn('journal_id', $openingIds)
            ->whereHas('journal', $inRange)->distinct()->pluck('journal_id');
        $lines = JournalLine::with('account')->whereIn('journal_id', $journalIds)->whereNotIn('coa_account_id', $cashIds)->get();
        $groups = collect(['operasi' => [], 'investasi' => [], 'pendanaan' => []]);
        foreach ($lines as $line) {
            $group = $line->account->cash_flow_group ?: 'operasi';
            $label = $line->account->statement_group ?: $line->account->name;
            $amount = Money::cents($line->credit) - Money::cents($line->debit);
            $items = $groups[$group];
            $items[$label] = ($items[$label] ?? 0) + $amount;
            $groups[$group] = $items;
        }
        $cashBalance = fn (string $date) => $cashIds->sum(fn ($id) => Ledger::balanceCents($id, $date));
        $opening = $cashBalance(Carbon::parse($from)->subDay()->toDateString())
            + JournalLine::whereIn('journal_id', $openingIds)->whereIn('coa_account_id', $cashIds)->get()->sum(fn ($l) => Money::cents($l->debit) - Money::cents($l->credit));
        $totals = $groups->map(fn ($items) => array_sum($items));

        return ['groups' => $groups, 'totals' => $totals, 'net' => $totals->sum(), 'opening' => $opening, 'closing' => $cashBalance($to), 'from' => $from, 'to' => $to];
    }

    /** Pendapatan & beban per pusat biaya. */
    public static function byCostCenter(string $from, string $to): Collection
    {
        $rows = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('coa_accounts', 'coa_accounts.id', '=', 'journal_lines.coa_account_id')
            ->whereIn('journals.status', Journal::POSTED)->whereBetween('journals.date', [$from, $to])->whereIn('coa_accounts.type', ['pendapatan', 'beban'])
            ->where(fn ($q) => self::withoutYearClose($q))
            ->groupBy('journal_lines.cost_center_id', 'coa_accounts.type')
            ->select('journal_lines.cost_center_id', 'coa_accounts.type', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))->get();

        return CostCenter::orderBy('code')->get()->map(function (CostCenter $center) use ($rows) {
            $mine = $rows->where('cost_center_id', $center->id);
            $revenue = $mine->where('type', 'pendapatan')->sum(fn ($r) => Money::cents($r->c) - Money::cents($r->d));
            $expense = $mine->where('type', 'beban')->sum(fn ($r) => Money::cents($r->d) - Money::cents($r->c));

            return ['center' => $center, 'revenue' => $revenue, 'expense' => $expense, 'net' => $revenue - $expense];
        })->filter(fn ($r) => $r['revenue'] || $r['expense'])->values();
    }

    /** Buku pembantu piutang/utang per pihak dengan umur (FIFO: pelunasan mengurangi tagihan tertua). */
    public static function subLedger(string $kind, string $date): Collection
    {
        $accountIds = CoaAccount::where($kind === 'piutang' ? 'is_receivable' : 'is_payable', true)->pluck('id');
        $lines = JournalLine::with('journal:id,date')->whereIn('coa_account_id', $accountIds)->whereNotNull('party_id')
            ->whereHas('journal', fn ($q) => $q->whereIn('status', Journal::POSTED)->where('date', '<=', $date))->get()
            ->sortBy(fn ($l) => [$l->journal->date->toDateString(), $l->journal_id])->groupBy('party_id');
        $asOf = Carbon::parse($date);

        return $lines->map(function ($partyLines, $partyId) use ($kind, $asOf) {
            $open = []; // [tanggal, sisa]
            foreach ($partyLines as $line) {
                $increase = $kind === 'piutang' ? Money::cents($line->debit) - Money::cents($line->credit) : Money::cents($line->credit) - Money::cents($line->debit);
                if ($increase > 0) {
                    $open[] = [$line->journal->date, $increase];
                    continue;
                }
                $pay = -$increase;
                foreach ($open as $i => [$d, $rest]) {
                    $take = min($rest, $pay);
                    $open[$i][1] -= $take;
                    $pay -= $take;
                    if ($pay === 0) {
                        break;
                    }
                }
            }
            $aging = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '>90' => 0];
            foreach ($open as [$d, $rest]) {
                $days = $d->diffInDays($asOf);
                $aging[$days <= 30 ? '0-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '>90'))] += $rest;
            }

            return ['party' => Party::find($partyId), 'balance' => array_sum($aging), 'aging' => $aging];
        })->filter(fn ($r) => $r['balance'] !== 0)->sortBy(fn ($r) => $r['party']->name)->values();
    }

    /** Rasio (PRD 9.14). */
    public static function ratios(string $from, string $to): array
    {
        $activity = self::activities($from, $to);
        $position = self::position($to);
        $program = $activity['expenses']->filter(fn ($r) => $r['account']->statement_group === 'Beban program')->sum('amount');
        $months = max(1, (int) round(Carbon::parse($from)->diffInMonths(Carbon::parse($to)->addDay())));
        $students = Member::where('category', 'siswa')->where('status', 'aktif')->count();
        $revenue = $activity['revenues']->reject(fn ($r) => $r['account']->code === '4-9000');
        $sources = $revenue->groupBy(fn ($r) => $r['account']->statement_group ?: $r['account']->name)->map->sum('amount')->filter();

        return [
            'program_ratio' => $activity['expenseTotal'] ? round($program / $activity['expenseTotal'] * 100, 1) : null,
            'cost_per_student' => $students ? intdiv($activity['expenseTotal'], $students * $months) : null,
            'students' => $students, 'months' => $months,
            'current_ratio' => $position['totals']['liabilities'] ? round($position['totals']['current'] / $position['totals']['liabilities'], 2) : null,
            'sources' => $sources->map(fn ($v) => ['amount' => $v, 'percent' => $revenue->sum('amount') ? round($v / $revenue->sum('amount') * 100, 1) : 0]),
        ];
    }

    /** Realisasi vs anggaran per akun & pusat biaya untuk satu tahun. */
    public static function budgetRealization(int $year, ?string $until = null): Collection
    {
        $until ??= "{$year}-12-31";

        return Budget::with('account', 'costCenter')->where('year', $year)->get()->map(function (Budget $b) use ($year, $until) {
            $actual = self::realized($b->coa_account_id, $b->cost_center_id, "{$year}-01-01", $until);
            $budget = Money::cents($b->amount);

            return ['budget' => $b, 'planned' => $budget, 'actual' => $actual, 'percent' => $budget ? round($actual / $budget * 100, 1) : null];
        })->sortBy(fn ($r) => [$r['budget']->account->code, $r['budget']->costCenter->code])->values();
    }

    /** Realisasi (sisi normal akun) per akun & pusat biaya. */
    public static function realized(int $accountId, int $costCenterId, string $from, string $to): int
    {
        $row = JournalLine::query()->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.coa_account_id', $accountId)->where('journal_lines.cost_center_id', $costCenterId)
            ->whereIn('journals.status', Journal::POSTED)->whereBetween('journals.date', [$from, $to])->where(fn ($q) => self::withoutYearClose($q))
            ->selectRaw('COALESCE(SUM(journal_lines.debit),0) d, COALESCE(SUM(journal_lines.credit),0) c')->first();
        $net = Money::cents($row->d) - Money::cents($row->c);

        return CoaAccount::find($accountId)->normal_balance === 'debit' ? $net : -$net;
    }
}
