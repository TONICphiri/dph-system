<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = now()->year;
        $sequence = fake()->unique()->numberBetween(1, 99999999);

        return [
            'national_id' => fake()->unique()->regexify('[A-Z][0-9]{9}'),
            'dhp_id' => sprintf('DHP-%d-%08d', $year, $sequence),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date(max: now()->subYears(18)),
            'gender' => fake()->randomElement(['M', 'F']),
            'phone_number' => fake()->numerify('+265 8## ### ###'),
            'address' => fake()->address(),
            'village' => fake()->city(),
            'district' => fake()->city(),
            'status' => 'active',
            'is_child' => false,
            'guardian_id' => null,
            'registered_at' => now(),
        ];
    }
}