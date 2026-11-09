<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\BlogAwaitingReview;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Berita & Artikel (PRD 8.6). */
class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:blog-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(Blog::class, 'blog');
    }

    public function index()
    {
        return view('dashboard.blog.index');
    }

    public function datatable(Request $request)
    {
        $user = $request->user();
        $blogs = Blog::select('blogs.*')->with('user', 'category')
            // penerbit melihat semua; penulis lain hanya tulisannya
            ->unless($user->can('publish', Blog::class), fn ($q) => $q->where('blogs.user_id', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('blogs.status', $request->input('status')));

        return DataTables::eloquent($blogs)
            ->editColumn('status', fn ($b) => Blog::STATUSES[$b->status])
            ->editColumn('updated_at', fn ($b) => $b->updated_at->translatedFormat('d M Y'))
            ->addColumn('action', fn ($b) => view('components.dashboard.row-actions', [
                'show' => route('blog.show', $b),
                'edit' => $user->can('update', $b) ? route('blog.edit', $b) : null,
                'delete' => $user->can('delete', $b) ? route('blog.destroy', $b) : null,
                'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.blog.form', ['blog' => new Blog(['status' => 'draft']), 'categories' => Category::orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $blog = Blog::create($data + [
            'user_id' => $request->user()->id,
            // PRD 8.6: tulisan atas nama PD/PC penulis (admin PC = PC-nya, anggota = PC keanggotaan)
            'organization_id' => $request->user()->organization_id ?? $request->user()->member?->organization_id,
        ]);
        $this->syncTags($blog, $request->input('tags'));
        $this->notifyReviewers($blog, null);

        return redirect()->route('blog.edit', $blog)->with('success', $this->statusMessage($blog));
    }

    public function show(Blog $blog)
    {
        return view('dashboard.blog.show', ['blog' => $blog->load('user', 'category', 'tags')]);
    }

    public function edit(Blog $blog)
    {
        return view('dashboard.blog.form', ['blog' => $blog->load('tags'), 'categories' => Category::orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, Blog $blog)
    {
        $before = $blog->status;
        $blog->update($this->validated($request, $blog)); // slug tidak berubah (dibuat sekali)
        $this->syncTags($blog, $request->input('tags'));
        $this->notifyReviewers($blog, $before);

        return redirect()->route($request->user()->can('update', $blog) ? 'blog.edit' : 'blog.show', $blog)->with('success', $this->statusMessage($blog));
    }

    public function destroy(Blog $blog)
    {
        $blog->delete(); // soft delete; thumbnail disimpan untuk pemulihan
        session()->flash('success', 'Tulisan dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Blog $blog = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:300',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|' . Upload::IMAGE_RULE,
            'status' => ['nullable', Rule::in(array_keys(Blog::STATUSES))],
            'published_at' => 'nullable|date',
        ]);
        unset($data['tags']);
        $data['body'] = Html::clean($data['body']);
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = Upload::store($request->file('thumbnail'), 'blog', $blog?->thumbnail);
        }

        // PRD 8.6 AC: penulis non-penerbit hanya bisa draft atau review; `published` dari request diabaikan
        if ($request->user()->can('publish', Blog::class)) {
            $data['status'] ??= 'published';
            $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $blog?->published_at ?? now()) : null;
        } else {
            $data['status'] = ($data['status'] ?? null) === 'draft' ? 'draft' : 'review';
            unset($data['published_at']);
        }

        return $data;
    }

    private function syncTags(Blog $blog, ?string $tags): void
    {
        $ids = collect(explode(',', (string) $tags))->map(fn ($t) => trim($t))->filter()->unique(fn ($t) => Str::slug($t))
            ->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
        $blog->tags()->sync($ids);
    }

    /** 8.13: email "blog menunggu review" ke editor saat tulisan masuk antrean. */
    private function notifyReviewers(Blog $blog, ?string $before): void
    {
        if ($blog->status === 'review' && $before !== 'review') {
            Notification::send(User::permission('blog-publish')->where('is_active', true)->get(), new BlogAwaitingReview($blog));
        }
    }

    private function statusMessage(Blog $blog): string
    {
        return match ($blog->status) {
            'published' => 'Tulisan diterbitkan.',
            'review' => 'Tulisan disimpan dan menunggu review editor.',
            default => 'Draf disimpan.',
        };
    }
}
