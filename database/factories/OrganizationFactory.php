<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $city = $this->faker->unique()->city;

        return [
            'parent_id' => fn () => Organization::active()->id,
            'type' => 'pc',
            'name' => 'PC AKP2I ' . $city,
            'code' => 'JABAR-' . strtoupper($this->faker->unique()->bothify('???##')),
            'city' => $city,
            'is_active' => true,
        ];
    }
}
