<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Skema;
use Illuminate\Database\Seeder;

/** Contoh agenda (dev): open house publik, kajian anggota, kelas angkatan, Sedulur Selawase yang sudah lewat. */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        $open = Event::factory()->create(['title' => 'Open House Rumah Mantaaba Sigaling', 'type' => 'open_house', 'quota' => 50]);
        Participant::factory()->count(3)->create(['event_id' => $open->id]);
        Event::factory()->create(['title' => 'Kajian Tajug: Fiqih Muamalah untuk Pebisnis', 'audience' => 'anggota'])->syncParticipants();
        if ($batch = Batch::where('status', 'running')->first()) {
            $kelas = Event::factory()->create(['title' => 'Kelas FND: Komunikasi Bisnis', 'type' => 'kelas', 'audience' => 'angkatan', 'batch_id' => $batch->id,
                'skema_id' => Skema::where('code', 'FND')->value('id'), 'status' => 'done', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(2)]);
            $kelas->syncParticipants();
            $kelas->participants()->take(2)->get()->each->markPresent();
        }
        Event::factory()->create(['title' => 'Majelis Sedulur Selawase', 'type' => 'sedulur_selawase', 'audience' => 'anggota', 'status' => 'done',
            'starts_at' => now()->subMonth(), 'ends_at' => now()->subMonth()->addHours(3)])->syncParticipants();
    }
}
