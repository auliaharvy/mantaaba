<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Blog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => rtrim($this->faker->sentence(6), '.'),
            'excerpt' => $this->faker->sentence(18),
            'body' => '<p>' . implode('</p><p>', $this->faker->paragraphs(4)) . '</p>',
            'thumbnail' => null,
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-6 months'),
            'category_id' => fn () => \App\Models\Category::inRandomOrder()->value('id'),
            'user_id' => User::inRandomOrder()->first()
        ];
    }
}
