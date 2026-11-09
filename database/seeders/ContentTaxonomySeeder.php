<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/** Kategori berita awal (PRD 13.2), bisa diubah redaksi. */
class ContentTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Kabar Asrama', 'Kebun & Kolam', 'Kajian', 'Program', 'Pengumuman'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
