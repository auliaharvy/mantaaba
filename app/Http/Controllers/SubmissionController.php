<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\LessonProgress;
use App\Models\Skema;
use App\Notifications\AssignmentReviewed;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Antrean Tugas Masuk (PRD 9.6): umpan balik + nilai 0–100, buka ulang. */
class SubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:learning-view')->only(['index', 'datatable', 'show']);
        $this->middleware('permission:learning-review')->only(['review', 'reopen']);
    }

    public function index()
    {
        return view('dashboard.submission.index', ['skemas' => Skema::ordered()->pluck('name', 'id'), 'batches' => Batch::orderByDesc('code')->pluck('code', 'id')]);
    }

    public function datatable(Request $request)
    {
        $query = LessonProgress::query()->whereNotNull('submitted_at')->with('user:id,name', 'lesson:id,title,module_id', 'lesson.module:id,skema_id', 'lesson.module.skema:id,code')
            ->select('lesson_progress.*')
            ->when($request->input('state', 'belum') === 'belum', fn ($q) => $q->whereNull('reviewed_at'))
            ->when($request->input('state') === 'sudah', fn ($q) => $q->whereNotNull('reviewed_at'))
            ->when($request->filled('skema'), fn ($q) => $q->whereHas('lesson.module', fn ($m) => $m->where('skema_id', $request->input('skema'))))
            ->when($request->filled('batch'), fn ($q) => $q->whereHas('user.member', fn ($m) => $m->where('batch_id', $request->input('batch'))));

        return DataTables::eloquent($query)
            ->addColumn('skema', fn ($p) => $p->lesson->module->skema->code)
            ->editColumn('submitted_at', fn ($p) => $p->submitted_at->translatedFormat('d M Y H:i'))
            ->editColumn('score', fn ($p) => $p->reviewed_at ? $p->score : 'Belum dinilai')
            ->addColumn('action', fn ($p) => view('components.dashboard.row-actions', ['show' => route('submission.show', $p), 'edit' => null, 'delete' => null, 'confirm' => null])->render())
            ->rawColumns(['action'])->addIndexColumn()->make(true);
    }

    public function show(LessonProgress $progress)
    {
        abort_unless($progress->submitted_at, 404);

        return view('dashboard.submission.show', ['progress' => $progress->load('user.member', 'lesson.module.skema', 'reviewer')]);
    }

    public function review(Request $request, LessonProgress $progress)
    {
        abort_unless($progress->submitted_at, 404);
        $data = $request->validate(['score' => 'required|integer|min:0|max:100', 'feedback' => 'required|string|max:5000']);
        $progress->update($data + ['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $progress->user->notify(new AssignmentReviewed($progress));

        return redirect()->route('submission.index')->with('success', 'Tugas dinilai.');
    }

    public function reopen(LessonProgress $progress)
    {
        $progress->update(['reviewed_at' => null, 'score' => null]);

        return back()->with('success', 'Tugas dibuka ulang: siswa bisa mengirim perbaikan.');
    }
}
