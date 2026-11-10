<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use App\Models\Gallery;
use App\Support\Html;
use App\Support\Upload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Unit bisnis & laboratorium kehidupan (PRD 9.1.5). Pusat biaya otomatis ditambahkan di M5a. */
class BusinessUnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cms-view')->only(['index', 'datatable']);
        $this->authorizeResource(BusinessUnit::class, 'business_unit');
    }

    public function index()
    {
        return view('dashboard.business-unit.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(BusinessUnit::query())
            ->editColumn('type', fn ($u) => BusinessUnit::TYPES[$u->type])
            ->editColumn('is_public', fn ($u) => $u->is_public ? 'Tampil' : 'Tersembunyi')
            ->addColumn('action', fn ($u) => view('components.dashboard.row-actions', [
                'show' => $u->is_public ? route('site.units.show', $u->slug) : null,
                'edit' => route('business-unit.edit', $u), 'delete' => route('business-unit.destroy', $u), 'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.business-unit.form', $this->formData(new BusinessUnit()));
    }

    public function store(Request $request)
    {
        BusinessUnit::create($this->validated($request));

        return redirect()->route('business-unit.index')->with('success', 'Unit bisnis ditambahkan.');
    }

    public function edit(BusinessUnit $businessUnit)
    {
        return view('dashboard.business-unit.form', $this->formData($businessUnit));
    }

    public function update(Request $request, BusinessUnit $businessUnit)
    {
        $businessUnit->update($this->validated($request, $businessUnit));

        return redirect()->route('business-unit.index')->with('success', 'Unit bisnis diubah.');
    }

    public function destroy(BusinessUnit $businessUnit)
    {
        Upload::delete($businessUnit->logo);
        Upload::delete($businessUnit->cover);
        $businessUnit->delete();
        session()->flash('success', 'Unit bisnis dihapus.');

        return response()->noContent();
    }

    private function formData(BusinessUnit $unit): array
    {
        return ['unit' => $unit, 'galleries' => Gallery::latest('published_at')->pluck('title', 'id')];
    }

    private function validated(Request $request, ?BusinessUnit $unit = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(BusinessUnit::TYPES))],
            'field' => 'nullable|string|max:255',
            'summary' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'logo' => 'nullable|' . Upload::IMAGE_RULE,
            'cover' => 'nullable|' . Upload::IMAGE_RULE,
            'gallery_id' => 'nullable|exists:galleries,id',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['body'] = Html::clean($data['body'] ?? null);
        foreach (['logo' => 400, 'cover' => 1600] as $field => $width) {
            unset($data[$field]);
            if ($request->hasFile($field)) {
                $data[$field] = Upload::store($request->file($field), 'units', $unit?->{$field}, $width);
            }
        }

        return $data + ['is_public' => $request->boolean('is_public'), 'sort_order' => (int) $request->input('sort_order')];
    }
}
