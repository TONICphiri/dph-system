<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Health Centre',
            'facility_code' => strtoupper(fake()->unique()->lexify('???') . str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT)),
            'facility_type' => 'Health Centre',
            'district' => fake()->city(),
            'region' => fake()->randomElement(['Northern', 'Central', 'Southern']),
            'status' => 'active',
        ];
    }
}