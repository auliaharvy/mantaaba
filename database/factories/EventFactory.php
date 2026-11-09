<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'organization_id' => fn () => \App\Models\Organization::active()->id,
            'title' => $this->faker->randomElement(['PPL', 'Seminar', 'Webinar']) . ' ' . rtrim($this->faker->sentence(4), '.'),
            'body' => '<p>' . $this->faker->paragraph(4) . '</p>',
            'thumbnail' => null,
            'type' => $this->faker->randomElement(['ppl', 'seminar', 'webinar']),
            'code' => strtoupper($this->faker->unique()->bothify('EV####??')),
            'mode' => 'offline',
            'location' => $this->faker->city,
            'starts_at' => $start = $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'ends_at' => (clone $start)->modify('+8 hours'),
            'quota' => 100,
            'audience' => 'publik',
            'fee_member' => 0,
            'fee_public' => 0,
            'skp_hours' => 8,
            'status' => 'open'
        ];
    }
}
