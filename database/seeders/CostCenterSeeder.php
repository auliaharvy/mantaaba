<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;

/** Pusat biaya (PRD 9.13.1) + satu per unit bisnis. */
class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['UMUM', 'Yayasan/Umum', 'umum'], ['PND', 'Pendidikan', 'program'], ['ASR', 'Asrama', 'program'], ['DPR', 'Dapur', 'program'],
            ['KBN', 'Kebun', 'program'], ['KLM', 'Kolam', 'program'], ['KND', 'Kendaraan', 'operasional']] as [$code, $name, $type]) {
            CostCenter::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type]);
        }
        BusinessUnit::where('type', 'unit_bisnis')->each(fn ($unit) => CostCenter::forUnit($unit));
    }
}
