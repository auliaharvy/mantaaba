<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

/** Q1 belum dijawab: PC contoh, bisa diubah/ditambah admin PD. */
class OrganizationSeeder extends Seeder
{
    public const SAMPLE_PCS = [
        'BDG' => 'Kota Bandung',
        'BDGKAB' => 'Kabupaten Bandung',
        'BKS' => 'Kota Bekasi',
        'BGR' => 'Kota Bogor',
        'DPK' => 'Kota Depok',
        'CRB' => 'Kota Cirebon',
    ];

    public function run(): void
    {
        $pd = Organization::active();

        foreach (self::SAMPLE_PCS as $code => $city) {
            Organization::firstOrCreate(['code' => "JABAR-{$code}"], [
                'parent_id' => $pd->id,
                'type' => 'pc',
                'name' => "PC AKP2I {$city}",
                'city' => $city,
            ]);
        }
    }
}
