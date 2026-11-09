<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /** Akun demo per role; password semua "password". */
    public const ACCOUNTS = [
        'super_administrator' => ['Super Administrator', 'superadmin@mail.com'],
        'admin_pd' => ['Admin PD', 'admin@mail.com'],
        'editor' => ['Editor', 'editor@mail.com'],
        'admin_pc' => ['Admin PC', 'adminpc@mail.com'],
        'anggota' => ['Anggota', 'member@mail.com'],
        'umum' => ['Umum', 'general@mail.com'],
    ];

    public function run(): void
    {
        $this->call([RolePermissionSeeder::class, OrganizationSeeder::class]);
        $pc = Organization::pc()->orderBy('id')->first();

        $i = 0;
        foreach (self::ACCOUNTS as $role => [$name, $email]) {
            User::factory()->create([
                'name' => $name,
                'gender' => 'L',
                'birthplace' => 'Bandung',
                'date_of_birth' => now(),
                'phone_number' => '0812345678' . str_pad(++$i, 2, '0', STR_PAD_LEFT),
                'email' => $email,
                'password' => Hash::make('password'),
                'address' => 'Bandung',
                'organization_id' => $role === 'admin_pc' ? $pc->id : null,
            ])->assignRole($role);
        }
    }
}
