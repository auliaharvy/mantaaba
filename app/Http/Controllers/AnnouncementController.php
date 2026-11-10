<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Batch;
use App\Notifications\AnnouncementPublished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Pengumuman dashboard (PRD 9.19). */
class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:announcement-view')->only(['index', 'datatable']);
        $this->authorizeResource(Announcement::class, 'announcement');
    }

    public function index()
    {
        return view('dashboard.announcement.index');
    }

    public function datatable()
    {
        return DataTables::eloquent(Announcement::query()->with('batch:id,code'))
            ->editColumn('audience', fn ($a) => Announcement::AUDIENCES[$a->audience] . ($a->batch ? ' ' . $a->batch->code : ''))
            ->editColumn('published_at', fn ($a) => $a->published_at?->translatedFormat('d M Y H:i') ?? 'Draf')
            ->editColumn('title', fn ($a) => ($a->is_pinned ? '📌 ' : '') . e($a->title))
            ->addColumn('action', fn ($a) => view('components.dashboard.row-actions', ['show' => null, 'edit' => route('announcement.edit', $a), 'delete' => route('announcement.destroy', $a), 'confirm' => null])->render())
            ->rawColumns(['title', 'action'])->addIndexColumn()->make(true);
    }

    public function create()
    {
        return view('dashboard.announcement.form', ['announcement' => new Announcement(['audience' => 'semua', 'published_at' => now()]), 'batches' => Batch::orderByDesc('code')->pluck('code', 'id')]);
    }

    public function store(Request $request)
    {
        $announcement = Announcement::create($this->validated($request) + ['created_by' => $request->user()->id]);
        $this->maybeEmail($request, $announcement);

        return redirect()->route('announcement.index')->with('success', 'Pengumuman disimpan.');
    }

    public function edit(Announcement $announcement)
    {
        return view('dashboard.announcement.form', ['announcement' => $announcement, 'batches' => Batch::orderByDesc('code')->pluck('code', 'id')]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));
        $this->maybeEmail($request, $announcement);

        return redirect()->route('announcement.index')->with('success', 'Pengumuman diubah.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        session()->flash('success', 'Pengumuman dihapus.');

        return response()->noContent();
    }

    private function maybeEmail(Request $request, Announcement $announcement): void
    {
        if ($request->boolean('send_email') && $announcement->published_at?->isPast()) {
            Notification::send($announcement->recipients(), new AnnouncementPublished($announcement));
            session()->flash('success', 'Pengumuman disimpan dan dikirim ke email sasaran.');
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
            'batch_id' => 'nullable|required_if:audience,angkatan|exists:batches,id',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
        ]);

        return ['batch_id' => $data['audience'] === 'angkatan' ? $data['batch_id'] : null, 'is_pinned' => $request->boolean('is_pinned')] + $data;
    }
}
