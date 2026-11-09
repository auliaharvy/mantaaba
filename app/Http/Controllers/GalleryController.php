<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Support\Upload;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GalleryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cms-view')->only(['index', 'datatable']);
        $this->authorizeResource(Gallery::class, 'gallery');
    }

    public function index()
    {
        return view('dashboard.gallery.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Gallery::select('galleries.*')->withCount('photos'))
            ->editColumn('published_at', fn ($g) => $g->published_at?->translatedFormat('d M Y') ?? 'Draf')
            ->addColumn('action', fn ($g) => view('components.dashboard.row-actions', [
                'show' => route('site.gallery.show', $g->slug), 'edit' => route('gallery.edit', $g), 'delete' => route('gallery.destroy', $g),
                'confirm' => 'Album beserta semua fotonya akan dihapus.',
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.gallery.form', ['gallery' => new Gallery(['published_at' => now()]), 'events' => $this->events()]);
    }

    public function store(Request $request)
    {
        $gallery = Gallery::create($this->validated($request));

        return redirect()->route('gallery.edit', $gallery)->with('success', 'Album dibuat. Unggah foto di bawah.');
    }

    public function edit(Gallery $gallery)
    {
        return view('dashboard.gallery.form', ['gallery' => $gallery->load('photos'), 'events' => $this->events()]);
    }

    public function update(Request $request, Gallery $gallery)
    {
        $gallery->update($this->validated($request, $gallery));

        return redirect()->route('gallery.edit', $gallery)->with('success', 'Album disimpan.');
    }

    public function destroy(Gallery $gallery)
    {
        $gallery->photos->each(fn ($p) => Upload::delete($p->image));
        Upload::delete($gallery->cover);
        $gallery->delete();
        session()->flash('success', 'Album dihapus.');

        return response()->noContent();
    }

    /** Upload banyak foto sekaligus (PRD 8.5). */
    public function storePhotos(Request $request, Gallery $gallery)
    {
        $this->authorize('update', $gallery);
        $request->validate(['photos' => 'required|array|max:30', 'photos.*' => Upload::IMAGE_RULE]);
        $order = (int) $gallery->photos()->max('sort_order');
        foreach ($request->file('photos') as $file) {
            $gallery->photos()->create(['image' => Upload::store($file, "galleries/{$gallery->id}"), 'sort_order' => ++$order]);
        }

        return redirect()->route('gallery.edit', $gallery)->with('success', count($request->file('photos')) . ' foto diunggah.');
    }

    /** Simpan keterangan & urutan semua foto sekaligus. */
    public function updatePhotos(Request $request, Gallery $gallery)
    {
        $this->authorize('update', $gallery);
        $data = $request->validate(['photos' => 'array', 'photos.*.caption' => 'nullable|string|max:255', 'photos.*.sort_order' => 'nullable|integer|min:0']);
        foreach ($data['photos'] ?? [] as $id => $values) {
            $gallery->photos()->whereKey($id)->update(['caption' => $values['caption'] ?? null, 'sort_order' => (int) ($values['sort_order'] ?? 0)]);
        }
        $gallery->touch(); // bersihkan cache beranda

        return redirect()->route('gallery.edit', $gallery)->with('success', 'Keterangan & urutan foto disimpan.');
    }

    public function destroyPhoto(GalleryPhoto $photo)
    {
        $this->authorize('update', $photo->gallery);
        Upload::delete($photo->image);
        $photo->delete();

        return redirect()->route('gallery.edit', $photo->gallery_id)->with('success', 'Foto dihapus.');
    }

    private function validated(Request $request, ?Gallery $gallery = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'cover' => 'nullable|' . Upload::IMAGE_RULE,
            'event_id' => 'nullable|exists:events,id',
            'published_at' => 'nullable|date',
        ]);
        if ($request->hasFile('cover')) {
            $data['cover'] = Upload::store($request->file('cover'), 'galleries', $gallery?->cover);
        }

        return $data;
    }

    private function events()
    {
        return Event::orderByDesc('starts_at')->limit(100)->pluck('title', 'id');
    }
}
