<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'guest_name' => $this->faker->name,
            'guest_phone' => '0812' . $this->faker->numerify('########'),
            'status' => 'terdaftar',
        ];
    }
}
