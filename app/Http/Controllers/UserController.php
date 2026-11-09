<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AccountActivation;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

/** Pengguna & Hak Akses (PRD 5.2–5.4, 9.18). */
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-view')->only(['index', 'datatable']);
        $this->authorizeResource(User::class, 'user', ['except' => ['index']]);
    }

    public function index()
    {
        return view('dashboard.user.index');
    }

    public function datatable(Request $request)
    {
        $users = User::select('users.*')->with('roles')
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->input('status') === 'aktif'));

        return DataTables::eloquent($users)
            ->addColumn('role', fn ($u) => view('components.dashboard.role-badge', ['user' => $u])->render())
            ->editColumn('is_active', fn ($u) => $u->is_active ? '<span class="badge badge-light-success">Aktif</span>' : '<span class="badge badge-light-danger">Nonaktif</span>')
            ->addColumn('action', fn ($u) => view('dashboard.user.action', ['target' => $u])->render())
            ->rawColumns(['role', 'is_active', 'action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create(Request $request)
    {
        return view('dashboard.user.form', ['target' => new User(['is_active' => true]), 'roles' => $this->roleOptions($request->user())]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = DB::transaction(function () use ($data) {
            $user = User::create(collect($data)->only(['name', 'email', 'phone_number', 'is_active'])->all()
                + ['password' => Hash::make($data['password'] ?? Str::random(32))]);
            $this->assignRole($user, $data['role']);
            if ($package = config("mantaaba.default_package.{$data['role']}")) {
                $user->givePackage($package);
            }

            return $user;
        });

        return redirect()->route('user.edit', $user)->with('success', "Pengguna {$user->name} dibuat." . (empty($data['password']) ? ' Kirim tautan atur password agar ia bisa masuk.' : ''));
    }

    public function edit(Request $request, User $user)
    {
        return view('dashboard.user.form', ['target' => $user, 'roles' => $this->roleOptions($request->user())]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        $this->guardLastSuperAdmin($user, $data['role'], (bool) $data['is_active']);

        DB::transaction(function () use ($user, $data) {
            $user->update(collect($data)->only(['name', 'email', 'phone_number', 'is_active'])->all()
                + (empty($data['password']) ? [] : ['password' => Hash::make($data['password'])]));
            if (! $user->hasRole($data['role'])) {
                $this->assignRole($user, $data['role']);
            }
        });

        return redirect()->route('user.index')->with('success', "Pengguna {$user->name} diperbarui.");
    }

    /** Tautan atur password: dikirim email + ditampilkan tautan WhatsApp (PRD 6.1: tanpa WA API). */
    public function passwordLink(User $user)
    {
        $this->authorize('update', $user);
        $url = route('password.reset', ['token' => Password::broker()->createToken($user), 'email' => $user->email]);
        $user->notify(new AccountActivation($url));

        $phone = preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $user->phone_number));
        $wa = $phone ? 'https://wa.me/' . $phone . '?text=' . rawurlencode("Assalamu'alaikum {$user->name}, silakan atur password akun " . config('app.name') . " Anda melalui tautan berikut: {$url}") : null;

        return back()->with('success', "Tautan atur password dikirim ke {$user->email}.")->with('wa_link', $wa);
    }

    public function permissions(User $user)
    {
        $this->authorize('managePermissions', $user);

        return view('dashboard.user.permissions', [
            'target' => $user,
            'granted' => $user->getDirectPermissions()->pluck('name')->all(),
            'packages' => collect(config('mantaaba.packages'))->map(fn ($p, $key) => ['label' => $p['label'], 'permissions' => Permissions::package($key)]),
        ]);
    }

    public function updatePermissions(Request $request, User $user)
    {
        $this->authorize('managePermissions', $user);
        $data = $request->validate(['permissions' => 'array', 'permissions.*' => ['string', Rule::in(Permissions::all())]]);

        $before = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
        $user->syncPermissions($data['permissions'] ?? []);
        $after = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
        if ($before !== $after) {
            ActivityLog::record('permissions_changed', $user, ['before' => ['permissions' => $before], 'after' => ['permissions' => $after]]);
        }

        return redirect()->route('user.permissions', $user)->with('success', "Hak akses {$user->name} disimpan.");
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'phone_number' => 'nullable|string|max:30',
            'role' => ['required', Rule::in($request->user()->assignableRoles())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }

    private function roleOptions(User $actor): array
    {
        return array_intersect_key(config('mantaaba.roles'), array_flip($actor->assignableRoles()));
    }

    /** Ganti role (satu role per pengguna) + batas jumlah super admin (PRD 5.2). */
    private function assignRole(User $user, string $role): void
    {
        if ($role === 'super_admin' && ! $user->isSuperAdmin()
            && User::role('super_admin')->count() >= config('mantaaba.max_super_admins')) {
            throw ValidationException::withMessages(['role' => 'Jumlah Super Admin sudah mencapai batas (' . config('mantaaba.max_super_admins') . ').']);
        }
        $before = $user->getRoleNames()->all();
        $user->syncRoles([$role]);
        if ($before !== [$role]) {
            ActivityLog::record('role_changed', $user, ['before' => ['roles' => $before], 'after' => ['roles' => [$role]]]);
        }
    }

    /** Super admin aktif terakhir tidak boleh diturunkan atau dinonaktifkan. */
    private function guardLastSuperAdmin(User $user, string $role, bool $active): void
    {
        $leaving = $user->isSuperAdmin() && ($role !== 'super_admin' || ! $active);
        if ($leaving && User::role('super_admin')->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            throw ValidationException::withMessages(['role' => 'Minimal harus ada satu Super Admin aktif.']);
        }
    }
}
