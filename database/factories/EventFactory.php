<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+2 months');

        return [
            'title' => 'Kajian ' . rtrim($this->faker->sentence(3), '.'),
            'body' => '<p>' . $this->faker->paragraph(3) . '</p>',
            'type' => 'kajian_tajug',
            'mode' => 'offline',
            'location' => 'Tajug Mantaaba',
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+2 hours'),
            'quota' => 100,
            'audience' => 'publik',
            'status' => 'open',
        ];
    }
}
