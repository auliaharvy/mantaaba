<?php

namespace App\Http\Controllers;

use App\Models\HealthRecord;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Catatan kesehatan siswa (PRD 9.4), privat untuk pemegang health-view. Tombol "Buat voucher" aktif di M5a. */
class HealthRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:health-view')->only(['index', 'datatable']);
        $this->authorizeResource(HealthRecord::class, 'health');
    }

    public function index()
    {
        return view('dashboard.health.index');
    }

    public function datatable()
    {
        return DataTables::eloquent(HealthRecord::query()->with('member:id,name,member_number')->select('health_records.*'))
            ->editColumn('date', fn ($r) => $r->date->translatedFormat('d M Y'))
            ->editColumn('action', fn ($r) => HealthRecord::ACTIONS[$r->action])
            ->editColumn('cost', fn ($r) => $r->cost > 0 ? 'Rp ' . number_format($r->cost, 0, ',', '.') : '-')
            ->addColumn('actions', fn ($r) => view('components.dashboard.row-actions', ['show' => route('member.show', [$r->member_id, 'tab' => 'kesehatan']),
                'edit' => route('health.edit', $r), 'delete' => route('health.destroy', $r), 'confirm' => null])->render())
            ->rawColumns(['actions'])->addIndexColumn()->make(true);
    }

    public function create(Request $request)
    {
        return view('dashboard.health.form', ['record' => new HealthRecord(['date' => today(), 'member_id' => $request->integer('member') ?: null, 'action' => 'istirahat']), 'members' => $this->members()]);
    }

    public function store(Request $request)
    {
        $record = HealthRecord::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('member.show', [$record->member_id, 'tab' => 'kesehatan'])->with('success', 'Catatan kesehatan disimpan.');
    }

    public function edit(HealthRecord $health)
    {
        return view('dashboard.health.form', ['record' => $health, 'members' => $this->members()]);
    }

    public function update(Request $request, HealthRecord $health)
    {
        $health->update($this->validated($request));

        return redirect()->route('health.index')->with('success', 'Catatan kesehatan diubah.');
    }

    public function destroy(HealthRecord $health)
    {
        $health->delete();
        session()->flash('success', 'Catatan dihapus.');

        return response()->noContent();
    }

    private function members()
    {
        return Member::where('status', 'aktif')->orderBy('name')->get(['id', 'name', 'member_number'])->mapWithKeys(fn ($m) => [$m->id => "{$m->name} ({$m->member_number})"]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'member_id' => 'required|exists:members,id',
            'date' => 'required|date|before_or_equal:today',
            'complaint' => 'required|string|max:255',
            'action' => ['required', Rule::in(array_keys(HealthRecord::ACTIONS))],
            'facility' => 'nullable|string|max:255',
            'cost' => 'nullable|numeric|min:0|max:1000000000',
            'note' => 'nullable|string|max:1000',
        ]);
    }
}
