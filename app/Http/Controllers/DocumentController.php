<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cms-view')->only(['index', 'datatable']);
        $this->authorizeResource(Document::class, 'document');
    }

    public function index()
    {
        return view('dashboard.document.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Document::query())
            ->editColumn('published_at', fn ($d) => $d->published_at?->translatedFormat('d M Y') ?? 'Draf')
            ->addColumn('action', fn ($d) => view('components.dashboard.row-actions', [
                'show' => asset('storage/' . $d->file), 'edit' => route('document.edit', $d), 'delete' => route('document.destroy', $d), 'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.document.form', ['document' => new Document(['published_at' => now()])]);
    }

    public function store(Request $request)
    {
        Document::create($this->validated($request));

        return redirect()->route('document.index')->with('success', 'Dokumen ditambahkan.');
    }

    public function edit(Document $document)
    {
        return view('dashboard.document.form', compact('document'));
    }

    public function update(Request $request, Document $document)
    {
        $document->update($this->validated($request, $document));

        return redirect()->route('document.index')->with('success', 'Dokumen diubah.');
    }

    public function destroy(Document $document)
    {
        Upload::delete($document->file);
        $document->delete();
        session()->flash('success', 'Dokumen dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Document $document = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', Rule::in(Document::CATEGORIES)],
            'file' => ($document ? 'nullable|' : 'required|') . Upload::PDF_RULE,
            'description' => 'nullable|string|max:1000',
            'published_at' => 'nullable|date',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        if ($request->hasFile('file')) {
            $data['file'] = Upload::store($request->file('file'), 'documents', $document?->file);
        }

        return ['sort_order' => (int) $request->input('sort_order')] + $data;
    }
}
