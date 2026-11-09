<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Thread;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Antrean laporan konten forum untuk moderator (PRD 8.7). */
class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:thread-moderate');
    }

    public function index()
    {
        return view('dashboard.report.index', ['open' => Report::whereNull('resolved_at')->count()]);
    }

    public function datatable(Request $request)
    {
        $reports = Report::select('reports.*')->with('user', 'reportable')
            ->when(! $request->boolean('all'), fn ($q) => $q->whereNull('resolved_at'));

        return DataTables::eloquent($reports)
            ->editColumn('created_at', fn ($r) => $r->created_at->translatedFormat('d M Y H:i'))
            ->addColumn('target', fn ($r) => view('dashboard.report.target', ['report' => $r])->render())
            ->addColumn('action', fn ($r) => view('dashboard.report.action', ['report' => $r])->render())
            ->rawColumns(['target', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    /** Tandai selesai; opsional sekaligus menurunkan konten (thread disembunyikan, komentar dihapus). */
    public function resolve(Request $request, Report $report)
    {
        if ($request->boolean('take_down') && $target = $report->reportable) {
            $target instanceof Thread ? $target->update(['status' => 'hidden']) : $target->delete();
        }
        // laporan lain untuk konten yang sama ikut selesai
        Report::where('reportable_type', $report->reportable_type)->where('reportable_id', $report->reportable_id)
            ->whereNull('resolved_at')->update(['resolved_at' => now(), 'resolved_by' => $request->user()->id]);

        return back()->with('success', $request->boolean('take_down') ? 'Konten diturunkan dan laporan ditutup.' : 'Laporan ditutup.');
    }
}
