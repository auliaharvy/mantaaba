<?php

namespace App\Services;

use App\Models\CashReconciliation;
use App\Models\CoaAccount;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Tutup bulan dengan ceklis (PRD 9.13.4) & tutup buku tahunan.
 * Jurnal otomatis bulanan didaftarkan di config('mantaaba.monthly_journals') (kelas dengan method generate(FiscalPeriod, User): ?Journal),
 * diberi source "monthly:{key}" + id periode sehingga menjalankan ulang tidak membuat jurnal ganda.
 */
class PeriodCloser
{
    public function __construct(private JournalPoster $poster)
    {
    }

    /** Status ceklis untuk ditampilkan. */
    public function checklist(FiscalPeriod $period): array
    {
        $end = $this->endOf($period);
        $cashAccounts = CoaAccount::where('is_header', false)->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->orderBy('code')->get();
        $recons = CashReconciliation::where('fiscal_period_id', $period->id)->get()->keyBy('coa_account_id');
        $trial = Ledger::trialBalance($end);

        return [
            'pending' => Journal::where('fiscal_period_id', $period->id)->whereIn('status', ['draft', 'diajukan'])->count(),
            'generators' => collect(config('mantaaba.monthly_journals', []))->map(fn ($class, $key) => [
                'key' => $key, 'label' => $class::LABEL,
                'journal' => Journal::where('source_type', "monthly:{$key}")->where('source_id', $period->id)->whereNotIn('status', ['dibalik', 'ditolak'])->first(),
            ])->values(),
            'cash' => $cashAccounts->map(fn ($a) => ['account' => $a, 'book' => Ledger::balanceCents($a->id, $end), 'recon' => $recons[$a->id] ?? null]),
            'balanced' => $trial->sum('debit') === $trial->sum('credit'),
        ];
    }

    /** Jalankan semua generator jurnal otomatis untuk periode; idempoten per periode. */
    public function generate(FiscalPeriod $period, User $user): int
    {
        $created = 0;
        foreach (config('mantaaba.monthly_journals', []) as $key => $class) {
            $exists = Journal::where('source_type', "monthly:{$key}")->where('source_id', $period->id)->whereNotIn('status', ['dibalik', 'ditolak'])->exists();
            if (! $exists && app($class)->generate($period, $user)) {
                $created++;
            }
        }

        return $created;
    }

    public function reconcile(FiscalPeriod $period, CoaAccount $account, string $actual, ?string $note, User $user): CashReconciliation
    {
        $book = Ledger::balanceCents($account->id, $this->endOf($period));
        if (Money::cents($actual) !== $book && blank($note)) {
            throw ValidationException::withMessages(['note' => "Ada selisih pada {$account->label()}: jelaskan atau buat voucher koreksi."]);
        }

        return CashReconciliation::updateOrCreate(['fiscal_period_id' => $period->id, 'coa_account_id' => $account->id],
            ['book_balance' => Money::decimal($book), 'actual_balance' => $actual, 'note' => $note, 'created_by' => $user->id]);
    }

    public function close(FiscalPeriod $period, User $user, bool $confirmPending = false): void
    {
        abort_if($period->isClosed(), 422, 'Periode sudah ditutup.');
        $this->generate($period, $user);
        $check = $this->checklist($period);
        // jurnal otomatis harus sudah disetujui Bendahara: periode tertutup tidak bisa menerima posting lagi
        $unposted = $check['generators']->filter(fn ($g) => $g['journal'] && $g['journal']->status !== 'terposting');
        if ($unposted->isNotEmpty()) {
            throw ValidationException::withMessages(['generators' => 'Posting dulu jurnal otomatis: ' . $unposted->pluck('label')->implode(', ') . '.']);
        }
        $missing = $check['cash']->filter(fn ($c) => ! $c['recon'] || ($c['recon']->differenceCents() !== 0 && blank($c['recon']->note)) || Money::cents($c['recon']->book_balance) !== $c['book']);
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['recon' => 'Rekonsiliasi belum lengkap/terbaru: ' . $missing->map(fn ($c) => $c['account']->name)->implode(', ') . '.']);
        }
        if (! $check['balanced']) {
            throw ValidationException::withMessages(['balance' => 'Neraca saldo tidak seimbang.']);
        }
        if ($check['pending'] && ! $confirmPending) {
            throw ValidationException::withMessages(['pending' => "Masih ada {$check['pending']} voucher draf/diajukan di periode ini; voucher tersebut tidak bisa diposting setelah periode ditutup. Konfirmasi untuk tetap menutup."]);
        }
        $period->update(['status' => 'closed', 'closed_by' => $user->id, 'closed_at' => now()]);
    }

    /**
     * Tutup buku tahunan: AJV per 31 Desember memindahkan saldo pendapatan & beban ke aset neto
     * (tanpa pembatasan → 3-1200; dengan pembatasan → config posting_rules.year_close.restricted). Syarat: Jan–Nov tertutup, Des terbuka.
     */
    public function closeYear(int $year, User $user): Journal
    {
        $periods = FiscalPeriod::where('year', $year)->get()->keyBy('month');
        foreach (range(1, 11) as $m) {
            if (! ($periods[$m] ?? null)?->isClosed()) {
                throw ValidationException::withMessages(['year' => 'Tutup dulu periode Januari–November ' . $year . '.']);
            }
        }
        if (Journal::where('source_type', 'year_close')->where('source_id', $year)->whereIn('status', ['diajukan', 'terposting'])->exists()) {
            throw ValidationException::withMessages(['year' => "Tutup buku {$year} sudah dibuat."]);
        }
        $date = "{$year}-12-31";
        $net = FinancialReports::movements("{$year}-01-01", $date);
        $lines = [];
        $toNet = ['tanpa' => 0, 'dengan' => 0];
        foreach (CoaAccount::whereIn('type', ['pendapatan', 'beban'])->where('is_header', false)->get() as $account) {
            $balance = $net[$account->id] ?? 0; // debit − kredit
            if ($balance === 0) {
                continue;
            }
            $lines[] = ['coa_account_id' => $account->id, $balance > 0 ? 'credit' : 'debit' => Money::decimal(abs($balance)), 'memo' => 'Penutupan ' . $year];
            $toNet[$account->restriction === 'dengan_pembatasan' ? 'dengan' : 'tanpa'] -= $balance;
        }
        abort_if(! $lines, 422, "Tidak ada saldo pendapatan/beban di {$year}.");
        foreach (['tanpa' => \App\Support\PostingRules::code('year_close.unrestricted'), 'dengan' => \App\Support\PostingRules::code('year_close.restricted')] as $group => $code) {
            if ($toNet[$group] !== 0) {
                $lines[] = ['coa_account_id' => CoaAccount::byCode($code)->id, $toNet[$group] > 0 ? 'credit' : 'debit' => Money::decimal(abs($toNet[$group])), 'memo' => 'Surplus/defisit ' . $year];
            }
        }
        $center = \App\Models\CostCenter::byCode('UMUM')?->id;
        // pusat biaya UMUM memenuhi aturan "pendapatan/beban wajib pusat biaya" pada baris penutup
        $lines = array_map(fn ($l) => $l + ['cost_center_id' => $center], $lines);

        return DB::transaction(function () use ($year, $date, $lines, $user) {
            $journal = $this->poster->createSystem('AJV', $date, "Penutupan buku tahun {$year}", $lines, $user, 'year_close', $year);

            return $this->poster->post($journal, $user);
        });
    }

    private function endOf(FiscalPeriod $period): string
    {
        return Carbon::create($period->year, $period->month)->endOfMonth()->toDateString();
    }
}
