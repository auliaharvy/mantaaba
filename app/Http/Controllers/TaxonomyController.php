<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ForumCategory;
use App\Models\Tag;
use Illuminate\Http\Request;

/** Kategori berita, tag, dan kategori forum (PRD 8.6, 8.7): daftar sederhana, slug tetap. */
class TaxonomyController extends Controller
{
    private const TYPES = [
        'kategori' => ['model' => Category::class, 'label' => 'Kategori Berita', 'permission' => 'blog-publish', 'usage' => 'blogs'],
        'tag' => ['model' => Tag::class, 'label' => 'Tag Berita', 'permission' => 'blog-publish', 'usage' => 'blogs'],
        'kategori-forum' => ['model' => ForumCategory::class, 'label' => 'Kategori Forum', 'permission' => 'thread-moderate', 'usage' => 'threads'],
    ];

    private function type(Request $request, string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_unless($request->user()->can(self::TYPES[$type]['permission']), 403);

        return self::TYPES[$type] + ['key' => $type];
    }

    public function index(Request $request, string $type)
    {
        $config = $this->type($request, $type);
        $items = $config['model']::withCount($config['usage'])->orderBy($type === 'kategori-forum' ? 'sort_order' : 'name')->get();

        return view('dashboard.taxonomy.index', compact('config', 'items'));
    }

    public function store(Request $request, string $type)
    {
        $config = $this->type($request, $type);
        $config['model']::create($this->validated($request, $config));

        return back()->with('success', "{$config['label']} ditambahkan.");
    }

    public function update(Request $request, string $type, int $id)
    {
        $config = $this->type($request, $type);
        $config['model']::findOrFail($id)->update($this->validated($request, $config)); // slug tetap

        return back()->with('success', "{$config['label']} diubah.");
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $config = $this->type($request, $type);
        $config['model']::findOrFail($id)->delete(); // FK nullOnDelete / pivot cascade

        return back()->with('success', "{$config['label']} dihapus.");
    }

    private function validated(Request $request, array $config): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]) + ['sort_order' => (int) $request->input('sort_order')];
    }
}
