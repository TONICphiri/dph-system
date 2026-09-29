<?php

namespace Database\Factories;

use App\Models\Citizen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    protected $model = Citizen::class;

    public function definition(): array
    {
        $year = now()->format('Y');

        return [
            'passport_id' => 'MW-DHP-'.$year.'-'.fake()->unique()->numerify('######'),
            'national_id' => fake()->boolean(70) ? fake()->unique()->bothify('????????') : null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'sex' => fake()->randomElement(['female', 'male']),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-1 year')->format('Y-m-d'),
            'district' => fake()->randomElement(['Blantyre', 'Lilongwe', 'Zomba', 'Mzuzu']),
            'village' => fake()->optional()->word(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
        ];
    }
}
