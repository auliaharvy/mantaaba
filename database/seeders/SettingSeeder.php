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
            'foundation_name' => 'Yayasan Mantaaba',
            'tagline' => 'Wadah pembinaan berbasis realitas kehidupan: akselerasi karakter & vokasi bisnis untuk pemuda, menuju masyarakat Indonesia yang transparan pajak.',
            'meta_description' => 'Rumah Mantaaba Sigaling (Yayasan Mantaaba): inkubasi karakter & vokasi bisnis 6 bulan, berasrama dan gratis, bagi pemuda lulusan SMA/sederajat.',
            'assessment_weights' => Setting::DEFAULT_WEIGHTS,
        ]);
    }
}
