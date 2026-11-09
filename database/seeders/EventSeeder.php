<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/** Contoh event M4: mendatang gratis, mendatang berbayar khusus anggota, dan satu yang sudah selesai. */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        Event::factory()->create(['title' => 'PPL Update Ketentuan Perpajakan Terbaru', 'type' => 'ppl', 'skp_hours' => 8]);
        Event::factory()->create([
            'title' => 'Brevet Pajak A & B Angkatan ' . now()->year, 'type' => 'brevet_a', 'audience' => 'publik',
            'fee_member' => 1500000, 'fee_public' => 2500000, 'quota' => 40, 'skp_hours' => null,
            'organization_id' => Organization::pc()->orderBy('id')->value('id'),
        ]);
        Event::factory()->create([
            'title' => 'Seminar Coretax DJP', 'type' => 'seminar', 'status' => 'done', 'skp_hours' => 4,
            'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()->addHours(4),
        ]);
    }
}
