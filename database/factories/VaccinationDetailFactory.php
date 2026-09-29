<?php

namespace Database\Factories;

use App\Models\VaccinationDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaccinationDetail>
 */
class VaccinationDetailFactory extends Factory
{
    protected $model = VaccinationDetail::class;

    public function definition(): array
    {
        return [
            'credential_id' => \App\Models\Credential::factory(),
            'vaccine_name' => fake()->randomElement(['BCG', 'Measles', 'HPV', 'Tetanus', 'COVID-19']),
            'dose_number' => fake()->numberBetween(1, 3),
            'administration_date' => now()->subDays(10)->toDateString(),
            'batch_number' => fake()->optional()->bothify('MW#####'),
            'next_dose_date' => fake()->optional()->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
        ];
    }
}
