<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role & permission PRD 5.2–5.3. Idempoten.
 * Role tidak membawa permission: akses modul diberikan langsung per pengguna (paket di config/mantaaba.php),
 * sehingga hak satu pengurus bisa dikurangi tanpa memengaruhi pengurus lain. super_admin lolos lewat Gate::before.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = Permissions::all();
        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        Permission::whereNotIn('name', $names)->delete();

        $roles = array_keys(config('mantaaba.roles'));
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])->syncPermissions([]);
        }
        Role::whereNotIn('name', $roles)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
