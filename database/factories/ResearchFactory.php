<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResearchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => 'Analisis ' . rtrim($this->faker->sentence(5), '.'),
            'authors' => $this->faker->name . ', ' . $this->faker->name,
            'abstract' => $this->faker->paragraph(5),
            'keywords' => implode(', ', $this->faker->words(3)),
            'category' => $this->faker->randomElement(['PPh', 'PPN', 'Ketentuan Umum']),
            'date_of_publish' => $this->faker->dateTimeBetween('-3 years'),
            'file' => 'research/contoh.pdf',
            'status' => 'published',
        ];
    }
}
