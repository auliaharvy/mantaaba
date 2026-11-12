<?php

namespace Tests\Feature\M5b;

use App\Http\Controllers\ReportController;
use App\Models\Budget;
use App\Models\CoaAccount;
use App\Models\CostCenter;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Models\User;
use App\Services\FinancialReports;
use App\Services\JournalPoster;
use App\Services\Ledger;
use App\Services\PeriodCloser;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Laporan ISAK 35, anggaran, tutup bulan & tutup buku (PRD 9.13.4–9.14, AC M5b). */
class FinanceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $maker;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        $this->maker = User::where('email', 'pengurus@mail.com')->firstOrFail();
        $this->approver = User::factory()->create()->assignRole('pengurus')->givePackage('bendahara');
    }

    private function acc(string $code): int
    {
        return CoaAccount::byCode($code)->id;
    }

    private function cc(string $code): int
    {
        return CostCenter::byCode($code)->id;
    }

    private function posted(string $type, string $date, array $lines, string $description = 'Uji'): Journal
    {
        $poster = app(JournalPoster::class);
        $journal = $poster->saveDraft(['voucher_type' => $type, 'date' => $date, 'description' => $description], $lines, $this->maker);
        $journal->forceFill(['attachments' => [['path' => 'journals/x.pdf', 'name' => 'x.pdf']]])->save();
        $poster->submit($journal, $this->maker, true);

        return $poster->post($journal, $this->approver);
    }

    /** Transaksi beragam: beban, donasi, utang, piutang (COA Mantaaba tidak memisahkan dana terikat). */
    private function scenario(): void
    {
        $date = today()->toDateString();
        $this->posted('BPV', $date, [['coa_account_id' => $this->acc('52230000'), 'debit' => 650000, 'cost_center_id' => $this->cc('ASR')], ['coa_account_id' => $this->acc('11220000'), 'credit' => 650000]]);
        $this->posted('BRV', $date, [['coa_account_id' => $this->acc('11210000'), 'debit' => 3000000], ['coa_account_id' => $this->acc('43000000'), 'credit' => 3000000, 'cost_center_id' => $this->cc('PND')]]);
        $party = \App\Models\Party::first()->id;
        $this->posted('PJV', $date, [['coa_account_id' => $this->acc('11810000'), 'debit' => 300000, 'cost_center_id' => $this->cc('DPR')], ['coa_account_id' => $this->acc('21100000'), 'credit' => 300000, 'party_id' => $party]]);
        $this->posted('RJV', $date, [['coa_account_id' => $this->acc('11400000'), 'debit' => 200000, 'party_id' => $party], ['coa_account_id' => $this->acc('79000000'), 'credit' => 200000, 'cost_center_id' => $this->cc('KBN')]]);
    }

    public function test_position_balances_and_cash_flow_reconciles_on_every_date()
    {
        $this->scenario();
        foreach ([today()->subMonth()->startOfMonth()->subDay(), today()->subMonth()->startOfMonth(), today()->subDays(3), today(), today()->endOfYear()] as $date) {
            $position = FinancialReports::position($date->toDateString());
            $this->assertSame($position['totals']['assets'], $position['totals']['liabilities_net_assets'], 'A = L + AN per ' . $date->toDateString());
        }
        foreach ([[today()->startOfYear(), today()], [today()->startOfMonth(), today()], [today()->subDays(2), today()]] as [$from, $to]) {
            $flow = FinancialReports::cashFlow($from->toDateString(), $to->toDateString());
            $this->assertSame($flow['closing'], $flow['opening'] + $flow['net'], 'kas awal + arus bersih = kas akhir ' . $from->toDateString());
        }
        $changes = FinancialReports::netAssetChanges(today()->startOfYear()->toDateString(), today()->toDateString());
        $position = FinancialReports::position(today()->toDateString());
        $this->assertSame($position['totals']['net_assets'], $changes['tanpa']['closing'] + $changes['dengan']['closing']);
    }

    public function test_report_figures_match_manual_calculation()
    {
        $this->scenario();
        $to = today()->toDateString();
        $from = today()->startOfYear()->toDateString();
        // demo: saldo awal 51 jt aset / 2 jt utang / 49 jt aset neto; CPV 412 rb; BRV donasi 2,5 jt; titipan anggota 250 rb − 50 rb (kas & utang titipan)
        $p = FinancialReports::position($to);
        $this->assertSame(Money::cents(51000000 + 2500000 - 650000 + 3000000 + 300000 + 200000 + 200000), $p['totals']['assets']);
        $this->assertSame(Money::cents(2000000 + 300000 + 200000), $p['totals']['liabilities']);
        $this->assertSame(Money::cents(49000000 + 2500000 - 650000 + 3000000 + 200000), $p['totals']['unrestricted'], 'saldo + donasi − listrik + donasi pendidikan + penjualan');
        $this->assertSame(0, $p['totals']['restricted'], 'COA Mantaaba: tidak ada akun dengan pembatasan');

        $a = FinancialReports::activities($from, $to);
        $this->assertSame(Money::cents(2500000 + 3000000 + 200000 - 650000), $a['surplusTanpa']);
        $this->assertSame(0, $a['surplusDengan']);

        $flow = FinancialReports::cashFlow($from, $to);
        $this->assertSame(Money::cents(36000000), $flow['opening'], 'saldo awal pembukuan = kas awal');
        $this->assertSame(Money::cents(-412000 + 2500000 - 650000 + 3000000 + 250000 - 50000), $flow['net']);

        $centers = FinancialReports::byCostCenter($from, $to)->keyBy(fn ($r) => $r['center']->code);
        $this->assertSame(Money::cents(650000), $centers['ASR']['expense']);
        $this->assertSame(Money::cents(200000), $centers['KBN']['revenue']);

        $payables = FinancialReports::subLedger('utang', $to);
        $this->assertSame(Money::cents(2300000 + 200000), $payables->sum('balance'), 'utang usaha + titipan anggota');
        $this->assertSame(Money::cents(200000), FinancialReports::subLedger('piutang', $to)->sum('balance'));
    }

    public function test_every_report_renders_as_page_pdf_and_csv()
    {
        $this->scenario();
        $user = User::where('email', 'superadmin@mail.com')->first();
        $this->actingAs($user)->get(route('report.index'))->assertOk();
        foreach (array_keys(ReportController::REPORTS) as $report) {
            $this->actingAs($user)->get(route('report.show', $report))->assertOk();
            $this->actingAs($user)->get(route('report.show', [$report, 'format' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
            $this->assertStringStartsWith('%PDF', $this->actingAs($user)->get(route('report.show', [$report, 'format' => 'pdf']))->assertOk()->getContent());
        }
        $this->actingAs(User::where('email', 'anggota@mail.com')->first())->get(route('report.show', 'posisi'))->assertForbidden();
        $this->actingAs($user)->get(route('report.show', 'tidak-ada'))->assertNotFound();
        // drill-down ke buku besar
        $this->actingAs($user)->get(route('report.show', 'posisi'))->assertSee(route('ledger.index', ['account' => $this->acc('11110000'), 'from' => today()->startOfYear()->toDateString(), 'to' => today()->toDateString()]));
    }

    public function test_budget_is_a_soft_stop()
    {
        Budget::updateOrCreate(['year' => today()->year, 'coa_account_id' => $this->acc('52230000'), 'cost_center_id' => $this->cc('ASR')], ['amount' => 1000000]);
        $poster = app(JournalPoster::class);
        $journal = $poster->saveDraft(['voucher_type' => 'BPV', 'date' => today()->toDateString(), 'description' => 'Listrik'],
            [['coa_account_id' => $this->acc('52230000'), 'debit' => 1500000, 'cost_center_id' => $this->cc('ASR')], ['coa_account_id' => $this->acc('11220000'), 'credit' => 1500000]], $this->maker);
        $journal->forceFill(['attachments' => [['path' => 'journals/x.pdf', 'name' => 'x.pdf']]])->save();

        $this->actingAs($this->maker)->post(route('journal.submit', $journal))->assertSessionHasErrors('budget');
        $this->assertSame('draft', $journal->fresh()->status);
        $this->actingAs($this->maker)->post(route('journal.submit', $journal), ['confirm_budget' => 1])->assertSessionHasNoErrors();
        $this->assertSame('diajukan', $journal->fresh()->status);
        $this->assertStringContainsString('melebihi anggaran', $journal->fresh()->budget_warning);
        $this->actingAs($this->approver)->get(route('journal.show', $journal))->assertSee('melebihi anggaran');

        $poster->post($journal->fresh(), $this->approver);
        $row = FinancialReports::budgetRealization(today()->year)->first(fn ($r) => $r['budget']->coa_account_id === $this->acc('52230000'));
        $this->assertSame(150.0, $row['percent']);
        $this->actingAs($this->approver)->get(route('budget.index'))->assertOk()->assertSee('150%');
    }

    public function test_month_close_checklist_is_idempotent_and_requires_reconciliation()
    {
        // generator uji: satu jurnal penyesuaian per periode
        config(['mantaaba.monthly_journals' => ['uji' => FakeMonthlyJournal::class]]);
        $period = FiscalPeriod::forDate(today()->subMonth());
        $closer = app(PeriodCloser::class);
        $this->assertSame(1, $closer->generate($period, $this->approver));
        $this->assertSame(0, $closer->generate($period, $this->approver), 'dijalankan ulang tidak membuat jurnal ganda');

        $admin = User::where('email', 'superadmin@mail.com')->first();
        // jurnal otomatis wajib diposting dulu (dibuat oleh $this->approver → disetujui Super Admin)
        $this->actingAs($admin)->post(route('period.close', $period), ['confirm_pending' => 1])->assertSessionHasErrors('generators');
        app(JournalPoster::class)->post(Journal::where('source_type', 'monthly:uji')->first(), $admin);
        $this->actingAs($admin)->post(route('period.close', $period))->assertSessionHasErrors('recon');
        $this->actingAs($admin)->get(route('period.show', $period))->assertOk()->assertSee('Rekonsiliasi kas');
        $cash = CoaAccount::where('is_header', false)->where(fn ($q) => $q->where('is_cash', true)->orWhere('is_bank', true))->get();
        $end = today()->subMonth()->endOfMonth()->toDateString();
        $first = $cash->first();
        $this->actingAs($admin)->post(route('period.reconcile', $period), ['coa_account_id' => $first->id, 'actual' => 1])->assertSessionHasErrors('note');
        foreach ($cash as $account) {
            $this->actingAs($admin)->post(route('period.reconcile', $period), ['coa_account_id' => $account->id, 'actual' => Money::decimal(Ledger::balanceCents($account->id, $end))])->assertSessionHasNoErrors();
        }
        $this->actingAs($admin)->post(route('period.close', $period), ['confirm_pending' => 1])->assertSessionHas('success');
        $this->assertTrue($period->fresh()->isClosed());
        $this->assertSame(1, Journal::where('source_type', 'monthly:uji')->where('source_id', $period->id)->count());
    }

    public function test_year_close_moves_surplus_to_net_assets_once()
    {
        $this->scenario();
        $year = today()->year;
        $before = FinancialReports::activities("{$year}-01-01", "{$year}-12-31");
        $closer = app(PeriodCloser::class);
        $this->expectValidation(fn () => $closer->closeYear($year, $this->approver), 'year');

        FiscalPeriod::where('year', $year)->where('month', '<', 12)->update(['status' => 'closed']);
        foreach (range(1, 12) as $m) {
            FiscalPeriod::firstOrCreate(['year' => $year, 'month' => $m], ['status' => $m < 12 ? 'closed' : 'open']);
        }
        $journal = $closer->closeYear($year, $this->approver);
        $this->assertSame('terposting', $journal->status);
        $this->expectValidation(fn () => $closer->closeYear($year, $this->approver), 'year');

        $after = FinancialReports::position("{$year}-12-31");
        $this->assertSame(['tanpa' => 0, 'dengan' => 0], $after['surplus'], 'pendapatan & beban bersaldo nol');
        $this->assertSame($after['totals']['assets'], $after['totals']['liabilities_net_assets']);
        $this->assertSame(Money::cents(49000000) + $before['surplusTanpa'], $after['totals']['unrestricted']);
        $activity = FinancialReports::activities("{$year}-01-01", "{$year}-12-31");
        $this->assertSame($before['surplusTanpa'], $activity['surplusTanpa'], 'laporan aktivitas tahun itu tidak berubah setelah penutupan');
        $this->assertSame($before['surplusDengan'], $activity['surplusDengan']);
    }

    private function expectValidation(callable $fn, string $key): void
    {
        try {
            $fn();
            $this->fail("ValidationException {$key} diharapkan");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }
}

class FakeMonthlyJournal
{
    public const LABEL = 'Jurnal uji';

    public function generate(FiscalPeriod $period, User $user): ?Journal
    {
        $date = \Illuminate\Support\Carbon::create($period->year, $period->month)->endOfMonth()->toDateString();
        $journal = app(JournalPoster::class)->createSystem('AJV', $date, 'Penyesuaian uji', [
            ['coa_account_id' => CoaAccount::byCode('52230000')->id, 'debit' => 1000, 'cost_center_id' => CostCenter::byCode('ASR')->id],
            ['coa_account_id' => CoaAccount::byCode('11900000')->id, 'credit' => 1000],
        ], $user, "monthly:uji", $period->id);

        return $journal;
    }
}
