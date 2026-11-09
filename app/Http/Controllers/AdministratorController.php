<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdministratorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:super_administrator']);
        $this->authorizeResource(User::class, 'administrator');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('dashboard.user.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $administrators = User::select('users.*')
                ->role(['anggota']);

            return DataTables::eloquent($administrators)
                ->addColumn('role', function ($administrator) {
                    return view('dashboard.user.role', compact('administrator'))->render();
                })
                ->addColumn('action', function ($administrator) {
                    return view('dashboard.user.action_create', compact('administrator'))->render();
                })
                ->rawColumns(['role', 'action'])
                ->addIndexColumn()
                ->make(true);
        }

        return view('dashboard.user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $administrator
     * @return \Illuminate\Http\Response
     */
    public function show(User $administrator, Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $administrator
     * @return \Illuminate\Http\Response
     */
    public function edit(User $administrator)
    {
        return view('dashboard.user.edit', compact('administrator'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $administrator
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $administrator)
    {
        $request->validate([
            'role' => ['required', Rule::in(array_keys(config('akp2i.roles')))]
        ]);

        if ($request->input('role') == 'super_administrator') {
            $superadmin = User::role(['super_administrator'])->get();
            $limit = $superadmin->count();
            if ($limit >= config('akp2i.max_super_admins') && ! $administrator->hasRole('super_administrator')) {
                return redirect()->route('administrator.index')->with('warning', 'Jumlah super administrator tidak dapat melebihi batas (5).');
            } else {
                $administrator->syncRoles($request->input('role'));
                return redirect()->route('administrator.index')->with('success', 'Hak akses ' . $request->input('name') . ' telah diubah.');
            }
        } else {
            $administrator->syncRoles($request->input('role'));
            return redirect()->route('administrator.index')->with('success', 'Hak akses ' . $request->input('name') . ' telah diubah.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $administrator
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $administrator)
    {
        //
    }

    /**
     * Yajra datatable
     */
    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $administrators = User::select('users.*')
                ->role([
                    'super_administrator',
                    'admin_pd'
                ]);

            return DataTables::eloquent($administrators)
                ->addColumn('role', function ($administrator) {
                    return view('dashboard.user.role', compact('administrator'))->render();
                })
                ->addColumn('action', function ($administrator) {
                    return view('dashboard.user.action', compact('administrator'))->render();
                })
                ->rawColumns(['role', 'action'])
                ->addIndexColumn()
                ->make(true);
        }
    }
}
