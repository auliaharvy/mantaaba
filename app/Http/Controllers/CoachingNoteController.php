<?php

namespace App\Http\Controllers;

use App\Models\CoachingNote;
use App\Models\Member;
use App\Models\User;
use App\Notifications\SeriousViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Catatan pembinaan (PRD 9.5): input cepat untuk banyak siswa sekaligus. */
class CoachingNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:coaching-view')->only(['index', 'datatable']);
    }

    public function index(Request $request)
    {
        $members = Member::where('status', 'aktif')->whereIn('category', ['siswa', 'alumni'])->orderBy('name')->get(['id', 'name', 'member_number', 'batch_id']);

        return view('dashboard.coaching.index', ['members' => $members, 'selected' => array_filter([$request->integer('member')])]);
    }

    public function datatable()
    {
        return DataTables::eloquent(CoachingNote::query()->with('member:id,name', 'author:id,name')->select('coaching_notes.*'))
            ->editColumn('date', fn ($n) => $n->date->translatedFormat('d M Y'))
            ->editColumn('type', fn ($n) => '<span class="badge badge-' . CoachingNote::TYPE_COLORS[$n->type] . '">' . CoachingNote::TYPES[$n->type] . '</span>')
            ->editColumn('dimension', fn ($n) => CoachingNote::DIMENSIONS[$n->dimension])
            ->addColumn('author_name', fn ($n) => $n->author?->name ?? 'Sistem')
            ->addColumn('action', fn ($n) => view('components.dashboard.row-actions', ['show' => route('member.show', [$n->member_id, 'tab' => 'pembinaan']), 'edit' => null,
                'delete' => auth()->user()->can('coaching-delete') ? route('coaching.destroy', $n) : null, 'confirm' => null])->render())
            ->rawColumns(['type', 'action'])->addIndexColumn()->make(true);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CoachingNote::class);
        $data = $request->validate([
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:members,id',
            'date' => 'required|date|before_or_equal:today',
            'type' => ['required', Rule::in(array_keys(CoachingNote::TYPES))],
            'dimension' => ['required', Rule::in(array_keys(CoachingNote::DIMENSIONS))],
            'body' => 'required|string|max:2000',
            'visibility' => ['required', Rule::in(array_keys(CoachingNote::VISIBILITIES))],
            'follow_up' => 'nullable|string|max:1000',
        ], ['member_ids.required' => 'Pilih minimal satu siswa.']);

        $members = Member::whereIn('id', array_unique($data['member_ids']))->get();
        DB::transaction(fn () => $members->each(fn (Member $m) => $m->coachingNotes()->create(collect($data)->except('member_ids')->all() + ['author_id' => $request->user()->id])));

        // PRD 9.5: batas pelanggaran 30 hari → notifikasi Ketua (keputusan tetap manual)
        if (in_array($data['type'], ['pelanggaran_sedang', 'pelanggaran_berat'], true)) {
            $members->filter->hasViolationAlert()->each(fn (Member $m) => Notification::send(User::withPermission('member-update'), new SeriousViolation($m)));
        }

        return back()->with('success', 'Catatan disimpan untuk ' . $members->count() . ' anggota.');
    }

    public function destroy(CoachingNote $coaching)
    {
        $this->authorize('delete', $coaching);
        $coaching->delete();
        session()->flash('success', 'Catatan dihapus.');

        return response()->noContent();
    }
}
