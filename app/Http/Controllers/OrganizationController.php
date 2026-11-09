<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class OrganizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:organization-view')->only(['index', 'show', 'datatable']);
        $this->authorizeResource(Organization::class, 'organization');
    }

    public function index()
    {
        return view('dashboard.organization.index');
    }

    public function datatable(Request $request)
    {
        $ids = $request->user()->scopedOrganizationIds();
        $organizations = Organization::select('organizations.*')->pc()->inActiveRegion()
            ->withCount(['members' => fn ($q) => $q->where('status', 'aktif')])
            ->when($ids !== null, fn ($q) => $q->whereIn('organizations.id', $ids));

        return DataTables::eloquent($organizations)
            ->editColumn('is_active', fn ($org) => $org->is_active ? 'Aktif' : 'Nonaktif')
            ->addColumn('action', fn ($org) => view('components.dashboard.row-actions', [
                'show' => route('organization.show', $org),
                'edit' => $request->user()->can('update', $org) ? route('organization.edit', $org) : null,
                'delete' => $request->user()->can('delete', $org) ? route('organization.destroy', $org) : null,
                'confirm' => null,
            ])->render())
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('dashboard.organization.form', ['organization' => new Organization(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $organization = Organization::create($this->validated($request) + [
            'type' => 'pc',
            'parent_id' => Organization::active()->id,
        ]);

        return redirect()->route('organization.show', $organization)->with('success', "{$organization->name} berhasil ditambahkan.");
    }

    public function show(Organization $organization)
    {
        $organization->loadCount(['members', 'members as active_members_count' => fn ($q) => $q->where('status', 'aktif')]);
        $board = $organization->activeBoardPeriod?->load('boardMembers.user', 'boardMembers.position', 'boardMembers.division');

        return view('dashboard.organization.show', compact('organization', 'board'));
    }

    public function edit(Organization $organization)
    {
        return view('dashboard.organization.form', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $organization->update($this->validated($request, $organization));

        return redirect()->route('organization.show', $organization)->with('success', 'Data organisasi telah diubah.');
    }

    public function destroy(Organization $organization)
    {
        // PRD 8.1: PC yang masih punya anggota hanya boleh dinonaktifkan
        if ($organization->members()->withTrashed()->exists()) {
            return response()->json(['message' => 'PC masih memiliki anggota. Nonaktifkan saja melalui menu Ubah.'], 422);
        }
        $organization->delete();
        session()->flash('success', "{$organization->name} telah dihapus.");

        return response()->noContent();
    }

    private function validated(Request $request, ?Organization $organization = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:20', Rule::unique('organizations')->ignore($organization)],
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'profile' => 'nullable|string|max:5000',
            'greeting' => 'nullable|string|max:5000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
