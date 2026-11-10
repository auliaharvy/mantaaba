<?php

namespace App\Http\Controllers;

use App\Models\DutyArea;
use Illuminate\Http\Request;

/** Area piket + ceklis tugas (PRD 9.9). */
class DutyAreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dorm-view')->only('index');
        $this->authorizeResource(DutyArea::class, 'duty_area', ['except' => ['index', 'show']]);
    }

    public function index()
    {
        return view('dashboard.duty.areas', ['areas' => DutyArea::withCount('duties')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        DutyArea::create($this->validated($request));

        return back()->with('success', 'Area piket ditambahkan.');
    }

    public function update(Request $request, DutyArea $dutyArea)
    {
        $dutyArea->update($this->validated($request));

        return back()->with('success', 'Area piket diubah.');
    }

    public function destroy(DutyArea $dutyArea)
    {
        abort_if($dutyArea->duties()->exists(), 422, 'Area sudah memiliki jadwal; nonaktifkan saja.');
        $dutyArea->delete();
        session()->flash('success', 'Area dihapus.');

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'description' => 'nullable|string|max:1000', 'checklist' => 'nullable|string|max:3000']);
        $data['checklist'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['checklist'] ?? '')))));
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
