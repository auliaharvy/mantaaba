<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/** CMS Halaman (PRD 8.5): slug tetap, hanya diubah. */
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
        return DataTables::eloquent(Page::select('pages.*')->with('editor'))
            ->editColumn('updated_at', fn ($p) => $p->updated_at->translatedFormat('d M Y H:i'))
            ->editColumn('is_published', fn ($p) => $p->is_published ? 'Terbit' : 'Draf')
            ->addColumn('action', fn ($p) => view('components.dashboard.row-actions', [
                'show' => route('site.page', $p->slug), 'edit' => route('page.edit', $p), 'delete' => null, 'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function edit(Page $page)
    {
        return view('dashboard.page.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'cover' => 'nullable|' . Upload::IMAGE_RULE,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
        ]);
        $data['body'] = Html::clean($data['body'] ?? null);
        if ($request->hasFile('cover')) {
            $data['cover'] = Upload::store($request->file('cover'), 'pages', $page->cover);
        }
        $page->update($data + ['is_published' => $request->boolean('is_published'), 'updated_by' => $request->user()->id]);

        return redirect()->route('page.edit', $page)->with('success', 'Halaman disimpan.');
    }
}
