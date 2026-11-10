<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AssetService;
use Illuminate\Database\Seeder;

/** Contoh aset (dev): mobil (aset lama) + pemakaian & servis, motor (beli transfer), tanah, kulkas dapur. */
class AssetDemoSeeder extends Seeder
{
    public function run(AssetService $assets): void
    {
        abort_if(app()->isProduction(), 500, 'AssetDemoSeeder dilarang di produksi.');
        $admin = User::where('email', 'superadmin@mail.com')->first();
        $pengurus = User::where('email', 'pengurus@mail.com')->first();
        $make = fn (array $data, string $recording) => $assets->register($data + AssetService::defaults($data['category']) + ['condition' => 'baik', 'source' => 'beli',
            'depreciation_start' => $data['category'] === 'tanah' ? null : $data['acquired_at']], $recording, null, $admin);

        $car = $make(['name' => 'Toyota Avanza', 'category' => 'kendaraan', 'location' => 'Garasi', 'pic_user_id' => $pengurus->id, 'acquired_at' => today()->subYears(2)->startOfMonth(),
            'acquisition_value' => 180000000, 'residual_value' => 60000000, 'opening_accumulated' => 27500000], 'saldo_awal');
        $car->vehicle()->create(['plate_number' => 'B 1234 XYZ', 'brand_type' => 'Toyota Avanza 1.3 G', 'year' => 2022, 'color' => 'Hitam', 'last_odometer' => 84000,
            'tax_due_at' => today()->addDays(30), 'stnk_due_at' => today()->addYears(3), 'service_interval_km' => 5000, 'service_interval_days' => 180,
            'last_service_km' => 80000, 'last_service_at' => today()->subMonths(4)]);
        $usage = $assets->startUsage($car, ['purpose' => 'Belanja pasar', 'destination' => 'Pasar Cibeber'], $pengurus);
        $assets->endUsage($usage, ['odometer_end' => 84032, 'fuel_cost' => 100000, 'fuel_liters' => 7.5], $pengurus);
        $assets->maintenance($car->fresh(), ['date' => today()->subDays(min(3, today()->day - 1)), 'type' => 'perbaikan', 'description' => 'Ganti aki', 'cost' => 850000, 'odometer' => 84010], $pengurus);

        $motor = $make(['name' => 'Honda Vario 125', 'category' => 'kendaraan', 'acquired_at' => today()->startOfMonth(), 'acquisition_value' => 17500000, 'residual_value' => 3500000], 'transfer');
        $motor->vehicle()->create(['plate_number' => 'B 4567 MTB', 'brand_type' => 'Honda Vario 125', 'year' => 2026, 'last_odometer' => 12, 'service_interval_km' => 2000, 'last_service_km' => 0]);
        $make(['name' => 'Tanah asrama', 'category' => 'tanah', 'location' => 'Sigaling', 'acquired_at' => today()->subYears(5), 'acquisition_value' => 150000000, 'source' => 'wakaf'], 'saldo_awal');
        $make(['name' => 'Kulkas dapur 2 pintu', 'category' => 'peralatan_dapur', 'location' => 'Dapur', 'acquired_at' => today()->subYear(), 'acquisition_value' => 4800000, 'opening_accumulated' => 1000000], 'saldo_awal');
    }
}
