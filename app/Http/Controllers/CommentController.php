<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

/** Ubah/hapus komentar forum: pemilik atau moderator (B3, PRD 8.7). */
class CommentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:comment-update'])->only(['edit', 'update']);
        $this->middleware(['permission:comment-delete'])->only(['destroy']);
        $this->authorizeResource(Comment::class, 'comment');
    }

    public function edit(Comment $comment)
    {
        return view('dashboard.comment.edit', ['comment' => $comment->load('thread')]);
    }

    public function update(Request $request, Comment $comment)
    {
        $comment->update($request->validate(['body' => 'required|string|max:5000']));

        return redirect()->to(route('site.forum.show', $comment->thread->slug) . '#komentar-' . $comment->id)->with('success', 'Komentar diubah.');
    }

    public function destroy(Request $request, Comment $comment)
    {
        $comment->delete();

        return $request->expectsJson()
            ? response()->noContent()
            : redirect()->route('site.forum.show', $comment->thread->slug)->with('success', 'Komentar dihapus.');
    }
}
