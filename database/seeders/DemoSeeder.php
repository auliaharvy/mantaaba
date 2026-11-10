<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data demo/presentasi (PRD 13.5): akun contoh + konten + seluruh modul, ditambah volume (DemoVolumeSeeder):
 * 12 siswa, ±40 voucher terposting, ±1 bulan riwayat dapur. Dilarang di produksi.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 500, 'DemoSeeder dilarang di produksi.');
        $this->call([UserSeeder::class, BlogSeeder::class, MembershipSeeder::class, EventSeeder::class, DormSeeder::class,
            AccountingDemoSeeder::class, OperationsDemoSeeder::class, AssetDemoSeeder::class]);
        // volume presentasi; test otomatis memakai data contoh ringan (angka manual di test) kecuali yang memanggilnya langsung
        if (! app()->runningUnitTests()) {
            $this->call(DemoVolumeSeeder::class);
        }
    }
}

