<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * PRD 13: data inti selalu dijalankan; akun & konten contoh (password "password") hanya di luar produksi.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SettingSeeder::class,
            PageSeeder::class,
            NavMenuSeeder::class,
            BusinessUnitSeeder::class,
            SkemaSeeder::class,
            ContentTaxonomySeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([UserSeeder::class, BlogSeeder::class, EventSeeder::class, ParticipantSeeder::class, MembershipSeeder::class]);
        }
    }
}
