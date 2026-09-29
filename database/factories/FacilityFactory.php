<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Health Centre',
            'code' => fake()->unique()->bothify('??-###'),
            'type' => fake()->randomElement(['health_centre', 'district_hospital', 'central_hospital', 'laboratory', 'other']),
            'ownership' => 'Government',
            'district_id' => District::factory(),
            'physical_address' => fake()->address(),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
