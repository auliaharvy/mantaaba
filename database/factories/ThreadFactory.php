<?php

namespace Database\Factories;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => rtrim($this->faker->sentence(7), '.') . '?',
            'body' => '<p>' . $this->faker->paragraph(4) . '</p>',
            'status' => 'published',
            'forum_category_id' => fn () => \App\Models\ForumCategory::inRandomOrder()->value('id'),
            'user_id' => User::inRandomOrder()->first()
        ];
    }
}
