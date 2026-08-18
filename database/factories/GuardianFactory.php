<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_id' => fake()->unique()->regexify('[A-Z][0-9]{9}'),
            'phone_number' => fake()->numerify('+265 8## ### ###'),
            'address' => fake()->address(),
            'relationship' => fake()->randomElement(['Mother', 'Father', 'Grandparent', 'Sibling', 'Relative', 'Other']),
            'status' => 'active',
        ];
    }
}