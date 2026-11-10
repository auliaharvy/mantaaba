<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/** Gudang inti (PRD 13): Dapur, Kebun & Kolam, Umum — pusat biaya pemakaian mengikuti gudang. */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['DPR', 'Gudang Dapur', 'DPR'], ['KBN', 'Gudang Kebun & Kolam', 'KBN'], ['UMM', 'Gudang Umum', 'UMUM']] as [$code, $name, $center]) {
            Warehouse::firstOrCreate(['code' => $code], ['name' => $name, 'cost_center_id' => CostCenter::byCode($center)?->id]);
        }
    }
}
