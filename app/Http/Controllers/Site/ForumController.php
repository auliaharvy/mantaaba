<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ForumCategory;
use App\Models\Report;
use App\Models\Thread;
use App\Notifications\ForumReplyPosted;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Forum publik (PRD 7, 8.7): baca publik (Q6 default), menulis wajib login & berhak. */
class ForumController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->filled('kategori') ? ForumCategory::where('slug', $request->query('kategori'))->firstOrFail() : null;
        $q = trim((string) $request->query('q'));
        $threads = Thread::published()->with('user', 'category')->withCount('comments')
            ->when($category, fn ($query) => $query->where('forum_category_id', $category->id))
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->orderByDesc('is_pinned')->orderByDesc('last_activity_at')
            ->paginate(15)->withQueryString();
        $categories = ForumCategory::orderBy('sort_order')->get();

        return view('site.forum.index', compact('threads', 'categories', 'category', 'q'));
    }

    public function show(Request $request, string $slug)
    {
        $thread = Thread::where('slug', $slug)->with('user', 'category')->firstOrFail();
        // thread tersembunyi hanya untuk pemilik & moderator
        abort_unless($thread->status === 'published' || $request->user()?->can('view', $thread), 404);

        $seen = $request->session()->get('viewed_threads', []);
        if (! in_array($thread->id, $seen, true)) {
            Thread::withoutTimestamps(fn () => $thread->increment('views'));
            $request->session()->push('viewed_threads', $thread->id);
        }

        $comments = $thread->rootComments()->with('user', 'replies.user')->paginate(20);

        return view('site.forum.show', compact('thread', 'comments'));
    }

    public function comment(Request $request, string $slug)
    {
        $thread = Thread::where('slug', $slug)->firstOrFail();
        $this->authorize('reply', $thread);
        abort_if($thread->is_locked, 403, 'Diskusi ini dikunci.'); // berlaku juga untuk super admin

        $data = $request->validate([
            'body' => 'required|string|max:5000',
            'parent_id' => ['nullable', Rule::exists('comments', 'id')->where('thread_id', $thread->id)],
        ]);
        // balasan satu level: membalas balasan = membalas komentar induknya
        if ($parent = Comment::find($data['parent_id'] ?? null)) {
            $data['parent_id'] = $parent->parent_id ?? $parent->id;
        }

        $comment = $thread->comments()->create($data + ['user_id' => $request->user()->id]);
        Thread::withoutTimestamps(fn () => $thread->update(['last_activity_at' => now()]));

        $owner = $thread->user;
        if ($owner && $owner->id !== $request->user()->id && $owner->notify_forum_replies && $owner->is_active) {
            $owner->notify(new ForumReplyPosted($comment));
        }

        return redirect()->to(route('site.forum.show', $thread->slug) . '#komentar-' . $comment->id)->with('success', 'Komentar terkirim.');
    }

    public function report(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:thread,comment',
            'id' => 'required|integer',
            'reason' => 'required|string|min:5|max:500',
        ]);
        $target = ($data['type'] === 'thread' ? Thread::class : Comment::class)::findOrFail($data['id']);

        Report::firstOrCreate(
            ['reportable_type' => $target->getMorphClass(), 'reportable_id' => $target->id, 'user_id' => $request->user()->id, 'resolved_at' => null],
            ['reason' => $data['reason']],
        );

        return back()->with('success', 'Terima kasih, laporan Anda akan ditinjau moderator.');
    }
}
