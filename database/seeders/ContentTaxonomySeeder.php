<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

/** Kategori awal berita & forum (contoh, bisa diubah editor). */
class ContentTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Berita Organisasi', 'Perpajakan', 'Pelatihan'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        foreach (['Umum' => 'Diskusi umum antar anggota', 'PPh' => 'Pajak Penghasilan', 'PPN' => 'Pajak Pertambahan Nilai', 'Profesi' => 'Etika & praktik konsultan pajak'] as $i => $desc) {
            ForumCategory::firstOrCreate(['name' => $i], ['description' => $desc]);
        }
    }
}
