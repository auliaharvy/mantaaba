<?php

namespace Database\Seeders;

use App\Models\BusinessUnit;
use App\Models\Gallery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Foto asli Rumah Mantaaba (database/seeders/media/mantaaba) untuk situs: disalin ke disk public (pages/blocks, dipakai blok halaman),
 * galeri "Kehidupan di Rumah Mantaaba", dan sampul unit. Data inti (juga produksi); tidak menimpa isi yang sudah diubah lewat CMS.
 */
class SiteMediaSeeder extends Seeder
{
    public const PHOTOS = [
        'bangunan_mantaaba_2.webp' => 'Rumah Mantaaba Sigaling, Cianjur',
        'bangunan_mantaaba_1.webp' => 'Asrama dan halaman Rumah Mantaaba',
        'kbm_1.jpg' => 'Kelas skema kompetensi',
        'kbm_2.webp' => 'Diskusi kelompok di ruang kelas',
        'kbm_3.webp' => 'Praktik di ruang komputer',
        'kerja_1.webp' => 'Pendampingan kerja bersama mentor',
        'sholat_1.webp' => 'Sholat berjamaah di tajug',
        'tadarus_1.webp' => 'Tadarus bersama selepas maghrib',
        'panen_ikan_2.webp' => 'Panen kolam ikan',
        'panen_ikan_3.webp' => 'Gembira saat panen ikan',
        'panen_ikan_4.webp' => 'Hasil panen kolam',
        'panen_ikan_1.webp' => 'Makan bersama hasil panen',
        'barista_1.webp' => 'Belajar meracik kopi',
    ];

    public function run(): void
    {
        $disk = Storage::disk('public');
        foreach (array_keys(self::PHOTOS) as $file) {
            if (! $disk->exists("pages/blocks/{$file}")) {
                $disk->put("pages/blocks/{$file}", file_get_contents(database_path("seeders/media/mantaaba/{$file}")));
            }
        }

        if (! Gallery::where('slug', 'kehidupan-di-rumah-mantaaba')->exists()) {
            $gallery = Gallery::create(['title' => 'Kehidupan di Rumah Mantaaba', 'slug' => 'kehidupan-di-rumah-mantaaba', 'published_at' => now(),
                'description' => 'Belajar, beribadah, bekerja, dan panen bersama di Rumah Mantaaba Sigaling.', 'cover' => 'pages/blocks/bangunan_mantaaba_1.webp']);
            foreach (array_keys(self::PHOTOS) as $i => $file) {
                $gallery->photos()->create(['image' => "pages/blocks/{$file}", 'caption' => self::PHOTOS[$file], 'sort_order' => $i + 1]);
            }
        }

        foreach (['Asrama' => 'bangunan_mantaaba_1.webp', 'Kolam Ikan' => 'panen_ikan_2.webp', 'Dapur' => 'panen_ikan_1.webp'] as $unit => $file) {
            BusinessUnit::where('name', $unit)->whereNull('cover')->update(['cover' => "pages/blocks/{$file}"]);
        }
    }
}
