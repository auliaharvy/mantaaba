<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Participant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'status' => 'verified',
            'amount' => 0,
            'verified_at' => now(),
            
            'event_id' => Event::inRandomOrder()->first(),
            'user_id' => User::inRandomOrder()->first()
        ];
    }
}
