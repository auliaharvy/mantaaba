<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * PRD 13: data inti selalu dijalankan; akun & konten contoh (password "password") hanya di luar produksi dan bila MANTAABA_DEMO
 * tidak false. Tanpa demo: berita & agenda awal saja; anggota, keuangan, kebun & kolam kosong.
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
            SiteMediaSeeder::class,
            SkemaSeeder::class,
            DutyAreaSeeder::class,
            CoaSeeder::class,
            CostCenterSeeder::class,
            WarehouseSeeder::class,
            ContentTaxonomySeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->isProduction() && config('mantaaba.demo')) {
            $this->call(DemoSeeder::class);
        } else {
            $this->call(StarterContentSeeder::class);
        }
    }
}
