<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vital>
 */
class VitalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'encounter_id' => \App\Models\Encounter::factory(),
            'patient_id' => \App\Models\Patient::factory(),
            'temperature' => fake()->randomFloat(1, 35.5, 39.5),
            'systolic_bp' => fake()->numberBetween(90, 160),
            'diastolic_bp' => fake()->numberBetween(60, 100),
            'heart_rate' => fake()->numberBetween(55, 105),
            'respiratory_rate' => fake()->numberBetween(12, 20),
            'weight' => fake()->randomFloat(2, 3, 90),
            'oxygen_saturation' => fake()->numberBetween(88, 100),
            'priority_level' => 'Low',
            'recorded_at' => now(),
        ];
    }

    /**
     * Indicate abnormal vitals.
     */
    public function abnormal(): static
    {
        return $this->state(fn (array $attributes) => [
            'temperature' => 40.5,
            'systolic_bp' => 180,
            'heart_rate' => 130,
            'respiratory_rate' => 30,
            'oxygen_saturation' => 85,
        ]);
    }
}