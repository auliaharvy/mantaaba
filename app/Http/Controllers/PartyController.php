<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

/** Pihak: donatur, pemasok, pelanggan, anggota, lembaga (PRD 9.13.1). */
class PartyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:coa-view')->only(['index', 'datatable', 'search']);
        $this->authorizeResource(Party::class, 'party', ['except' => ['index', 'show']]);
    }

    public function index()
    {
        return view('dashboard.party.index');
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(Party::query()->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type'))))
            ->editColumn('type', fn ($p) => Party::TYPES[$p->type])
            ->editColumn('is_active', fn ($p) => $p->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('action', fn ($p) => view('components.dashboard.row-actions', ['show' => null, 'edit' => route('party.edit', $p), 'delete' => null, 'confirm' => null])->render())
            ->rawColumns(['action'])->addIndexColumn()->make(true);
    }

    /** Pencarian untuk select2 di form voucher. */
    public function search(Request $request)
    {
        return Party::where('is_active', true)->where(fn ($q) => $q->where('name', 'like', '%' . $request->input('q') . '%')->orWhere('code', 'like', '%' . $request->input('q') . '%'))
            ->orderBy('name')->limit(20)->get()->map(fn ($p) => ['id' => $p->id, 'text' => "{$p->name} ({$p->code})"]);
    }

    public function create()
    {
        return view('dashboard.party.form', ['party' => new Party(['type' => 'pemasok']), 'members' => $this->members()]);
    }

    public function store(Request $request)
    {
        $party = Party::create($this->validated($request));

        return redirect()->route('party.index')->with('success', "Pihak {$party->code} ditambahkan.");
    }

    public function edit(Party $party)
    {
        return view('dashboard.party.form', ['party' => $party, 'members' => $this->members()]);
    }

    public function update(Request $request, Party $party)
    {
        $party->update($this->validated($request));

        return redirect()->route('party.index')->with('success', 'Pihak diubah.');
    }

    private function members()
    {
        return Member::orderBy('name')->get(['id', 'name', 'member_number'])->mapWithKeys(fn ($m) => [$m->id => "{$m->name} ({$m->member_number})"]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(Party::TYPES))],
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'member_id' => 'nullable|exists:members,id',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
