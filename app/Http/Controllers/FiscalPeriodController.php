<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use Illuminate\Http\Request;

/**
 * Periode buku (PRD 9.13.4). M5a: tutup/buka ulang sederhana; ceklis tutup bulan, rekonsiliasi & tutup buku tahunan di M5b.
 */
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

    public function close(Request $request, FiscalPeriod $period)
    {
        abort_unless($request->user()->can('period-close'), 403);
        abort_if($period->isClosed(), 422, 'Periode sudah ditutup.');
        $period->update(['status' => 'closed', 'closed_by' => $request->user()->id, 'closed_at' => now()]);

        return back()->with('success', "Periode {$period->label()} ditutup.");
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
