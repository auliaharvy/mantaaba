<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/** Identitas awal lembaga (PRD 13.1); detail kontak diisi pengurus lewat Setting Situs. */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate([], [
            'site_name' => 'Rumah Mantaaba Sigaling',
            'tagline' => 'Wadah pembinaan berbasis realitas kehidupan: akselerasi karakter & vokasi bisnis untuk pemuda, menuju masyarakat Indonesia yang transparan pajak.',
        ]);
    }
}
