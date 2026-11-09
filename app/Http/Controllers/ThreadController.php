<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\Thread;
use App\Support\Html;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** Forum (PRD 8.7) sisi dashboard: tulis/ubah thread sendiri; moderator menyembunyikan, mengunci, menyematkan. */
class ThreadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:thread-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(Thread::class, 'thread');
    }

    public function index()
    {
        return view('dashboard.thread.index');
    }

    public function datatable(Request $request)
    {
        $user = $request->user();
        $threads = Thread::select('threads.*')->with('user', 'category')->withCount('comments')
            ->unless($user->can('moderate', Thread::class), fn ($q) => $q->where('threads.user_id', $user->id));

        return DataTables::eloquent($threads)
            ->addColumn('flags', fn ($t) => view('dashboard.thread.flags', ['thread' => $t])->render())
            ->editColumn('last_activity_at', fn ($t) => $t->last_activity_at?->translatedFormat('d M Y H:i'))
            ->addColumn('action', fn ($t) => view('dashboard.thread.action', ['thread' => $t])->render())
            ->rawColumns(['flags', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.thread.form', ['thread' => new Thread, 'categories' => ForumCategory::orderBy('sort_order')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $thread = Thread::create($this->validated($request) + ['user_id' => $request->user()->id, 'status' => 'published']);

        return redirect()->route('site.forum.show', $thread->slug)->with('success', 'Diskusi diterbitkan.');
    }

    public function show(Thread $thread)
    {
        return redirect()->route('site.forum.show', $thread->slug);
    }

    public function edit(Thread $thread)
    {
        return view('dashboard.thread.form', ['thread' => $thread, 'categories' => ForumCategory::orderBy('sort_order')->pluck('name', 'id')]);
    }

    public function update(Request $request, Thread $thread)
    {
        $thread->update($this->validated($request)); // B2: user_id & slug tidak berubah

        return redirect()->route('thread.edit', $thread)->with('success', 'Diskusi diubah.');
    }

    public function destroy(Thread $thread)
    {
        $thread->delete();
        session()->flash('success', 'Diskusi dihapus.');

        return response()->noContent();
    }

    /** Moderasi (8.7): sembunyikan/tampilkan, kunci/buka, sematkan/lepas. */
    public function moderate(Request $request, Thread $thread)
    {
        $this->authorize('moderate', Thread::class);
        $data = $request->validate(['action' => 'required|in:hide,show,lock,unlock,pin,unpin']);
        $thread->update(match ($data['action']) {
            'hide' => ['status' => 'hidden'],
            'show' => ['status' => 'published'],
            'lock' => ['is_locked' => true],
            'unlock' => ['is_locked' => false],
            'pin' => ['is_pinned' => true],
            'unpin' => ['is_pinned' => false],
        });

        return back()->with('success', 'Status diskusi diperbarui.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:300',
            'body' => 'required|string|max:20000',
            'forum_category_id' => 'nullable|exists:forum_categories,id',
        ]);
        $data['body'] = Html::clean($data['body']);

        return $data;
    }
}
