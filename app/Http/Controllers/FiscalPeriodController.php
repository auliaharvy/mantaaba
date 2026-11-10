<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CoaAccount;
use App\Services\PeriodCloser;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use Illuminate\Http\Request;

/** Periode buku (PRD 9.13.4): ceklis tutup bulan, rekonsiliasi kas/bank, tutup buku tahunan, buka ulang (Super Admin). */
class FiscalPeriodController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('journal-view'), 403);
        $year = $request->integer('tahun') ?: now()->year;
        foreach (range(1, 12) as $month) {
            FiscalPeriod::firstOrCreate(['year' => $year, 'month' => $month]);
        }
        $periods = FiscalPeriod::where('year', $year)->orderBy('month')->get();
        $pending = Journal::whereIn('status', ['draft', 'diajukan'])->selectRaw('fiscal_period_id, count(*) as n')->groupBy('fiscal_period_id')->pluck('n', 'fiscal_period_id');

        return view('dashboard.period.index', compact('periods', 'year', 'pending'));
    }

    public function show(Request $request, FiscalPeriod $period, PeriodCloser $closer)
    {
        abort_unless($request->user()->can('journal-view'), 403);

        return view('dashboard.period.show', ['period' => $period, 'check' => $closer->checklist($period)]);
    }

    public function reconcile(Request $request, FiscalPeriod $period, PeriodCloser $closer)
    {
        abort_unless($request->user()->can('period-close'), 403);
        abort_if($period->isClosed(), 422, 'Periode sudah ditutup.');
        $data = $request->validate(['coa_account_id' => 'required|exists:coa_accounts,id', 'actual' => 'required|numeric', 'note' => 'nullable|string|max:500']);
        $closer->reconcile($period, CoaAccount::findOrFail($data['coa_account_id']), (string) $data['actual'], $data['note'] ?? null, $request->user());

        return back()->with('success', 'Rekonsiliasi disimpan.');
    }

    public function generate(Request $request, FiscalPeriod $period, PeriodCloser $closer)
    {
        abort_unless($request->user()->can('period-close'), 403);
        $count = $closer->generate($period, $request->user());

        return back()->with('success', "{$count} jurnal otomatis dibuat dan masuk antrean persetujuan.");
    }

    public function close(Request $request, FiscalPeriod $period, PeriodCloser $closer)
    {
        abort_unless($request->user()->can('period-close'), 403);
        $closer->close($period, $request->user(), $request->boolean('confirm_pending'));

        return redirect()->route('period.index', ['tahun' => $period->year])->with('success', "Periode {$period->label()} ditutup.");
    }

    public function closeYear(Request $request, PeriodCloser $closer)
    {
        abort_unless($request->user()->can('period-close'), 403);
        $year = (int) $request->validate(['year' => 'required|integer|min:2000|max:2100'])['year'];
        $journal = $closer->closeYear($year, $request->user());

        return redirect()->route('journal.show', $journal)->with('success', "Tutup buku {$year} terposting. Tutup periode Desember setelah ini.");
    }

    /** Buka ulang hanya Super Admin dengan alasan (PRD 9.13.4). */
    public function reopen(Request $request, FiscalPeriod $period)
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $reason = $request->validate(['reason' => 'required|string|max:500'])['reason'];
        $period->update(['status' => 'open', 'reopened_by' => $request->user()->id, 'reopen_reason' => $reason]);
        ActivityLog::record('period_reopened', $period, ['reason' => $reason]);

        return back()->with('success', "Periode {$period->label()} dibuka ulang.");
    }
}
