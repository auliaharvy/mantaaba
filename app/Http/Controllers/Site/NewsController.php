<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

/** Berita & Artikel publik (PRD 7, 8.6). */
class NewsController extends Controller
{
    public function index(Request $request, ?Category $category = null, ?Tag $tag = null)
    {
        $q = trim((string) $request->query('q'));
        $blogs = Blog::published()->with('user', 'category')
            ->when($category, fn ($query) => $query->where('category_id', $category->id))
            ->when($tag, fn ($query) => $query->whereHas('tags', fn ($t) => $t->whereKey($tag->id)))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%")))
            ->latest('published_at')
            ->paginate(9)->withQueryString();
        $categories = Category::withCount(['blogs' => fn ($query) => $query->published()])->orderBy('name')->get()->where('blogs_count', '>', 0);

        return view('site.news.index', compact('blogs', 'categories', 'category', 'tag', 'q'));
    }

    public function category(Request $request, Category $category)
    {
        return $this->index($request, $category);
    }

    public function tag(Request $request, Tag $tag)
    {
        return $this->index($request, null, $tag);
    }

    public function show(Request $request, string $slug)
    {
        $blog = Blog::published()->where('slug', $slug)->with('user', 'category', 'tags')->firstOrFail();

        // PRD 8.6: views +1 per sesi
        $seen = $request->session()->get('viewed_blogs', []);
        if (! in_array($blog->id, $seen, true)) {
            Blog::withoutTimestamps(fn () => $blog->increment('views'));
            $request->session()->push('viewed_blogs', $blog->id);
        }

        $related = Blog::published()->whereKeyNot($blog->id)
            ->when($blog->category_id, fn ($q) => $q->where('category_id', $blog->category_id))
            ->latest('published_at')->take(3)->get();

        return view('site.news.show', compact('blog', 'related'));
    }
}
