<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use Illuminate\Database\Seeder;

/** Laboratorium kehidupan dari Grand Design (PRD 13.2); unit bisnis afiliasi diisi pengurus. Hanya bila tabel kosong. */
class BusinessUnitSeeder extends Seeder
{
    public function run(): void
    {
        if (BusinessUnit::exists()) {
            return;
        }
        $labs = [
            ['Asrama', 'Kepemimpinan & resolusi konflik', 'Tempat tinggal bersama sekaligus latihan mengelola dinamika kelompok, jadwal piket, dan tanggung jawab bersama.'],
            ['Kebun', 'Ketahanan pangan', 'Siklus tanam hingga panen yang dicatat dan dihitung seperti unit produksi: modal, biaya, dan hasil.'],
            ['Kolam Ikan', 'Perikanan', 'Budidaya ikan sebagai praktik nyata perencanaan, pencatatan, dan pengendalian biaya produksi.'],
            ['Dapur', 'Manajemen anggaran & gizi', 'Perencanaan menu, belanja, dan pemakaian bahan dengan anggaran per orang per hari.'],
        ];
        foreach ($labs as $i => [$name, $field, $summary]) {
            BusinessUnit::create(['name' => $name, 'type' => 'laboratorium', 'field' => $field, 'summary' => $summary, 'sort_order' => $i]);
        }
    }
}
