<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/** Contoh agenda (dev): mendatang publik, mendatang khusus anggota, dan satu yang sudah selesai. */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::factory()->create(['title' => 'Open House Rumah Mantaaba Sigaling', 'type' => 'lainnya']);
        Event::factory()->create(['title' => 'Kajian Tajug: Fiqih Muamalah untuk Pebisnis', 'type' => 'seminar', 'audience' => 'anggota']);
        Event::factory()->create([
            'title' => 'Majelis Sedulur Selawase', 'type' => 'lainnya', 'status' => 'done',
            'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()->addHours(3),
        ]);
    }
}
