<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** PRD 13.1: satu Super Admin dari MANTAABA_ADMIN_EMAIL, password acak dicetak sekali. */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('mantaaba.admin_email');
        if (! $email || User::where('email', $email)->exists()) {
            return;
        }

        $password = Str::password(16, symbols: false);
        User::create(['name' => 'Super Admin', 'email' => $email, 'password' => Hash::make($password), 'is_active' => true])
            ->assignRole('super_admin');

        $this->command?->warn("Super Admin {$email} dibuat. Password: {$password} (simpan, lalu ganti setelah login).");
    }
}
