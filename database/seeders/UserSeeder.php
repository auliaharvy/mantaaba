<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Akun contoh untuk dev & test (password "password"). Tidak pernah dijalankan di produksi. */
class UserSeeder extends Seeder
{
    public const ACCOUNTS = [
        'super_admin' => ['Super Admin Demo', 'superadmin@mail.com'],
        'pengurus' => ['Pengurus Demo', 'pengurus@mail.com'],
        'anggota' => ['Anggota Demo', 'anggota@mail.com'],
    ];

    public function run(): void
    {
        abort_if(app()->isProduction(), 500, 'UserSeeder dilarang di produksi.');

        foreach (self::ACCOUNTS as $role => [$name, $email]) {
            $user = User::factory()->create(['name' => $name, 'email' => $email, 'password' => Hash::make('password')])->assignRole($role);
            if ($package = config("mantaaba.default_package.{$role}")) {
                $user->givePackage($package);
            }
        }
    }
}
