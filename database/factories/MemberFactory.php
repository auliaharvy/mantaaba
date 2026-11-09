<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->create()->assignRole('anggota')->id,
            'organization_id' => fn () => Organization::pc()->inRandomOrder()->value('id') ?? Organization::factory()->create()->id,
            'member_number' => 'AKP2I-' . $this->faker->unique()->numerify('######'),
            'kip_number' => $this->faker->optional()->numerify('KIP-#####'),
            'membership_type' => $this->faker->randomElement(array_keys(Member::TYPES)),
            'certification' => $this->faker->optional()->randomElement(['A', 'B', 'C']),
            'brevet' => $this->faker->optional()->randomElements(['A', 'B', 'C'], 2),
            'office_name' => $this->faker->optional()->company,
            'joined_at' => $this->faker->dateTimeBetween('-10 years', '-1 year'),
            'status' => 'aktif',
        ];
    }
}
