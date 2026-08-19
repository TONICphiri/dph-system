<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescription>
 */
class PrescriptionFactory extends Factory
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
            'medication_name' => fake()->randomElement(['Paracetamol', 'Amoxicillin', 'ORS Sachets', 'Coartem', 'Ibuprofen']),
            'dose' => fake()->randomElement(['500mg', '250mg', '1g', '10ml']),
            'frequency' => fake()->randomElement(['3x daily', '2x daily', '1x daily']),
            'quantity' => fake()->numberBetween(5, 60),
            'duration' => '7 days',
            'status' => 'pending',
            'prescribed_at' => now(),
        ];
    }
}