<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $event = Event::inRandomOrder()->first();
        User::inRandomOrder()->take(3)->get()->each(
            fn ($user) => Participant::factory()->create(['event_id' => $event->id, 'user_id' => $user->id])
        );
    }
}
