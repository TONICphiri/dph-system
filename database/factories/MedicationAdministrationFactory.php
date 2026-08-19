<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicationAdministration>
 */
class MedicationAdministrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admission_id' => \App\Models\Admission::factory(),
            'patient_id' => \App\Models\Patient::factory(),
            'administered_by_user_id' => \App\Models\User::factory(),
            'medication_name' => fake()->randomElement(['Paracetamol', 'Amoxicillin', 'ORS Sachets', 'Coartem']),
            'dose' => fake()->randomElement(['500mg', '250mg', '1g']),
            'route' => fake()->randomElement(['PO', 'IV', 'IM']),
            'administered_at' => now(),
            'notes' => null,
        ];
    }
}