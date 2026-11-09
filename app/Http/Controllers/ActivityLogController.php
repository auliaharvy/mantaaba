<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activity-log-view');
    }

    public function index()
    {
        $subjects = ActivityLog::query()->distinct()->orderBy('subject_type')->pluck('subject_type');

        return view('dashboard.activity-log.index', compact('subjects'));
    }

    public function datatable(Request $request)
    {
        $logs = ActivityLog::select('activity_logs.*')->with('user')
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->input('subject_type')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('activity_logs.created_at', $request->input('date')));

        return DataTables::eloquent($logs)
            ->editColumn('created_at', fn ($log) => $log->created_at?->translatedFormat('d M Y H:i'))
            ->addColumn('subject', fn ($log) => class_basename($log->subject_type) . ' #' . $log->subject_id)
            ->addColumn('detail', fn ($log) => view('dashboard.activity-log.changes', compact('log'))->render())
            ->rawColumns(['detail'])
            ->addIndexColumn()
            ->make(true);
    }
}
