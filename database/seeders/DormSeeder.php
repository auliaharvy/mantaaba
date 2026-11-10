<?php

namespace Database\Seeders;

use App\Models\Duty;
use App\Models\DutyArea;
use App\Models\Member;
use App\Models\Room;
use App\Services\DutyGenerator;
use Illuminate\Database\Seeder;

/** Contoh kamar & jadwal piket (dev). */
class DormSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(app()->isProduction(), 500, 'DormSeeder dilarang di produksi.');
        $putra = Room::create(['code' => 'A-1', 'name' => 'Kamar Al-Fatih', 'building' => 'Asrama Putra', 'capacity' => 4, 'gender' => 'L']);
        Room::create(['code' => 'A-2', 'name' => 'Kamar Ibnu Khaldun', 'building' => 'Asrama Putra', 'capacity' => 4, 'gender' => 'L']);
        Room::create(['code' => 'B-1', 'name' => 'Kamar Khadijah', 'building' => 'Asrama Putri', 'capacity' => 4, 'gender' => 'P']);
        $students = Member::where('category', 'siswa')->where('status', 'aktif')->get();
        $students->take(2)->each(fn ($m) => $m->update(['room_id' => $putra->id]));

        $generator = app(DutyGenerator::class);
        foreach (DutyArea::whereIn('name', ['Dapur', 'Kebun'])->get() as $area) {
            $generator->generate($area, today()->subDays(3), today()->addDays(6), ['pagi'], $students->pluck('id')->all(), 1);
        }
        Duty::whereDate('date', '<', today())->take(3)->update(['status' => 'selesai', 'done_at' => now()->subDay()]);
    }
}
