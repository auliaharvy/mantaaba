<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pusat biaya (PRD 9.13.1). */
class CostCenterController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('coa-view'), 403);

        return view('dashboard.cost-center.index', ['centers' => CostCenter::with('businessUnit')->withCount('lines')->orderBy('code')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CostCenter::class);
        CostCenter::create($this->validated($request));

        return back()->with('success', 'Pusat biaya ditambahkan.');
    }

    public function update(Request $request, CostCenter $costCenter)
    {
        $this->authorize('update', $costCenter);
        $costCenter->update($this->validated($request, $costCenter));

        return back()->with('success', 'Pusat biaya diubah.');
    }

    private function validated(Request $request, ?CostCenter $center = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('cost_centers')->ignore($center)],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(CostCenter::TYPES))],
        ], ['code.regex' => 'Kode huruf kapital/angka, mis. DPR.']) + ['is_active' => $request->boolean('is_active')];
    }
}
