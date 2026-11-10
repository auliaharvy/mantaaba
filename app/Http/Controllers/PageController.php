<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Blocks;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/**
 * CMS Halaman berbasis blok (PRD 9.1.3). Tombol editor (tambah/duplikat/hapus blok & item) dikirim sebagai `_op`
 * bersama seluruh form: perubahan disimpan dulu, lalu operasi diterapkan; tanpa JavaScript templating.
 */
class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cms-view')->only(['index', 'datatable']);
        $this->authorizeResource(Page::class, 'page');
    }

    public function index()
    {
        return view('dashboard.page.index');
    }

    public function datatable(Request $request)
    {
        $user = $request->user();

        return DataTables::eloquent(Page::select('pages.*')->with('editor'))
            ->editColumn('updated_at', fn ($p) => $p->updated_at->translatedFormat('d M Y H:i'))
            ->editColumn('is_published', fn ($p) => ($p->is_published ? 'Terbit' : 'Draf') . ($p->is_system ? ' · sistem' : ''))
            ->editColumn('slug', fn ($p) => $p->slug === 'home' ? '/' : '/' . $p->slug)
            ->addColumn('action', fn ($p) => view('components.dashboard.row-actions', [
                'show' => $p->is_published ? $p->url() : null,
                'edit' => route('page.edit', $p),
                'delete' => ! $p->is_system && $user->can('delete', $p) ? route('page.destroy', $p) : null,
                'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.page.create', ['page' => new Page()]);
    }

    public function store(Request $request)
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'max:100', 'unique:pages,slug', Rule::notIn(self::reservedSlugs())],
            'template' => ['required', Rule::in(array_keys(Page::TEMPLATES))],
        ], ['slug.not_in' => 'Alamat ini sudah dipakai halaman bawaan situs.']);

        $page = Page::create($data + ['is_published' => false, 'blocks' => [], 'updated_by' => $request->user()->id]);

        return redirect()->route('page.edit', $page)->with('success', 'Halaman dibuat sebagai draf. Tambahkan blok lalu terbitkan.');
    }

    public function edit(Page $page)
    {
        return view('dashboard.page.edit', [
            'page' => $page,
            'blocks' => old('blocks', $page->blocks ?? []),
            'previewUrl' => URL::temporarySignedRoute('page.preview', now()->addHour(), $page),
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'template' => ['required', Rule::in(array_keys(Page::TEMPLATES))],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'cover' => 'nullable|' . Upload::IMAGE_RULE,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
            'new_block' => ['nullable', Rule::in(array_keys(Blocks::definitions()))],
        ]);
        unset($data['new_block']);
        $data['body'] = Html::clean($data['body'] ?? $page->body);
        if ($request->hasFile('cover')) {
            $data['cover'] = Upload::store($request->file('cover'), 'pages', $page->cover);
        }
        $removed = $this->dropRemoved($request);
        $blocks = $page->template === 'blocks' && $request->has('blocks_present') ? Blocks::fromRequest($request) : ($page->blocks ?? []);
        [$blocks, $anchor, $message] = $this->applyOperation($request, $blocks);
        $message = $removed ?? $message;

        $page->update($data + [
            'blocks' => $blocks,
            // halaman beranda & kebijakan privasi selalu terbit
            'is_published' => $page->is_system || $request->boolean('is_published'),
            'show_in_sitemap' => $request->boolean('show_in_sitemap'),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->to(route('page.edit', $page) . ($anchor !== null ? "#blok-{$anchor}" : ''))->with('success', $message);
    }

    public function destroy(Page $page)
    {
        // berlaku juga untuk Super Admin (Gate::before tidak boleh melewati aturan ini)
        abort_if($page->is_system, 403, 'Halaman sistem tidak bisa dihapus.');
        $menus = $page->menus()->pluck('label');
        if ($menus->isNotEmpty()) {
            return response()->json(['message' => 'Halaman masih dipakai menu: ' . $menus->implode(', ') . '. Hapus atau ubah menu tersebut dulu.'], 422);
        }
        Upload::delete($page->cover);
        $page->delete();
        session()->flash('success', 'Halaman dihapus.');

        return response()->noContent();
    }

    /** Pratinjau halaman (termasuk draf) lewat tautan bertanda tangan, hanya untuk pengurus ber-izin. */
    public function preview(Page $page)
    {
        $this->authorize('view', $page);

        return app(Site\PageController::class)->render($page, preview: true);
    }

    /** Operasi tombol editor; posisi blok dihitung dari urutan form (setelah drag). */
    private function applyOperation(Request $request, array $blocks): array
    {
        $op = explode(':', (string) $request->input('_op'));
        $keys = array_keys($request->input('blocks', []));
        $pos = isset($op[1]) ? array_search($op[1], array_map('strval', $keys), true) : false;

        switch ($op[0]) {
            case 'add':
                if ($type = $request->input('new_block')) {
                    $blocks[] = Blocks::blank($type);

                    return [$blocks, count($blocks) - 1, 'Disimpan. Blok baru ditambahkan di bawah.'];
                }
                break;
            case 'duplicate':
                if ($pos !== false) {
                    array_splice($blocks, $pos + 1, 0, [$blocks[$pos]]);

                    return [$blocks, $pos + 1, 'Disimpan. Blok diduplikasi.'];
                }
                break;
            case 'add-item':
                if ($pos !== false && isset(Blocks::definitions()[$blocks[$pos]['type']]['items'])) {
                    $def = Blocks::definitions()[$blocks[$pos]['type']]['items'];
                    if (count($blocks[$pos]['data']['items']) < $def['max']) {
                        $blocks[$pos]['data']['items'][] = array_map(fn () => null, $def['fields']);
                    }

                    return [$blocks, $pos, 'Disimpan. Item ditambahkan.'];
                }
                break;
        }

        return [$blocks, null, 'Halaman disimpan.'];
    }

    /**
     * Hapus blok/item dari input sebelum validasi, agar blok atau item kosong yang baru ditambah bisa dibuang tanpa diisi.
     * Kunci lain tidak diubah supaya file unggahan tetap cocok dengan kuncinya.
     */
    private function dropRemoved(Request $request): ?string
    {
        $op = explode(':', (string) $request->input('_op'));
        $blocks = $request->input('blocks', []);
        if ($op[0] === 'remove' && isset($op[1], $blocks[$op[1]])) {
            unset($blocks[$op[1]]);
            $message = 'Disimpan. Blok dihapus.';
        } elseif ($op[0] === 'remove-item' && isset($op[2], $blocks[$op[1]]['data']['items'][$op[2]])) {
            unset($blocks[$op[1]]['data']['items'][$op[2]]);
            $message = 'Disimpan. Item dihapus.';
        } else {
            return null;
        }
        $request->merge(['blocks' => $blocks, '_op' => null]);

        return $message;
    }

    /** Segmen URL pertama yang sudah dipakai route (dashboard, berita, kontak, ...). */
    public static function reservedSlugs(): array
    {
        return collect(Route::getRoutes())->map(fn ($route) => explode('/', $route->uri())[0])
            ->reject(fn ($segment) => $segment === '' || str_starts_with($segment, '{'))
            ->push('home')->unique()->values()->all();
    }
}
