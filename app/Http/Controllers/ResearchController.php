<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Jurnal (PRD 8.8): dikelola editor/admin PD. */
class ResearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:research-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(Research::class, 'research');
    }

    public function index()
    {
        return view('dashboard.research.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Research::query())
            ->editColumn('date_of_publish', fn ($r) => $r->date_of_publish?->translatedFormat('d M Y'))
            ->editColumn('status', fn ($r) => $r->status === 'published' ? 'Terbit' : 'Draf')
            ->addColumn('action', fn ($r) => view('components.dashboard.row-actions', [
                'show' => $r->status === 'published' ? route('site.journal.show', $r->slug) : null,
                'edit' => $request->user()->can('update', $r) ? route('research.edit', $r) : null,
                'delete' => $request->user()->can('delete', $r) ? route('research.destroy', $r) : null,
                'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.research.form', ['research' => new Research(['status' => 'published', 'date_of_publish' => now()]), 'categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        Research::create($this->validated($request) + ['uploaded_by' => $request->user()->id]);

        return redirect()->route('research.index')->with('success', 'Jurnal ditambahkan.');
    }

    public function show(Research $research)
    {
        return redirect()->route('research.edit', $research);
    }

    public function edit(Research $research)
    {
        return view('dashboard.research.form', ['research' => $research, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Research $research)
    {
        $research->update($this->validated($request, $research));

        return redirect()->route('research.edit', $research)->with('success', 'Jurnal diubah.');
    }

    public function destroy(Research $research)
    {
        $research->delete(); // soft delete
        session()->flash('success', 'Jurnal dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request, ?Research $research = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'authors' => 'nullable|string|max:255',
            'abstract' => 'nullable|string|max:5000',
            'keywords' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'date_of_publish' => 'required|date',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'file' => ($research ? 'nullable|' : 'required|') . Upload::PDF_RULE,
            'cover' => 'nullable|' . Upload::IMAGE_RULE,
        ]);
        unset($data['file'], $data['cover']);
        // PDF di disk privat: hanya bisa diunduh lewat route penghitung bila terbit (PRD 8.8 AC)
        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('research', 'local');
            if ($research?->file) {
                Storage::disk('local')->delete($research->file);
            }
        }
        if ($request->hasFile('cover')) {
            $data['cover'] = Upload::store($request->file('cover'), 'research/covers', $research?->cover);
        }

        return $data;
    }

    private function categories()
    {
        return Research::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
    }
}
