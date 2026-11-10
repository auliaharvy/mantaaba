<?php

namespace Database\Seeders;

use App\Models\DutyArea;
use Illuminate\Database\Seeder;

/** Area piket (PRD 9.9 seed) + ceklis awal; hanya bila belum ada. */
class DutyAreaSeeder extends Seeder
{
    public function run(): void
    {
        if (DutyArea::exists()) {
            return;
        }
        $areas = [
            'Asrama' => ['Sapu & pel lantai kamar dan lorong', 'Rapikan sandal & rak sepatu', 'Buang sampah kamar'],
            'Dapur' => ['Cuci peralatan masak & makan', 'Lap meja dan kompor', 'Catat bahan yang dipakai di log dapur'],
            'Kebun' => ['Siram tanaman', 'Cabut gulma', 'Catat kondisi tanaman di log kebun'],
            'Kolam' => ['Beri pakan ikan sesuai jadwal', 'Cek kualitas & ketinggian air', 'Catat di log kolam'],
            'Tajug' => ['Sapu & pel lantai', 'Rapikan sajadah & mushaf', 'Bersihkan tempat wudhu'],
            'Kebersihan Umum' => ['Sapu halaman', 'Bersihkan kamar mandi umum', 'Kumpulkan & pilah sampah'],
        ];
        foreach ($areas as $name => $checklist) {
            DutyArea::create(['name' => $name, 'checklist' => $checklist, 'is_active' => true]);
        }
    }
}
