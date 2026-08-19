<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admission>
 */
class AdmissionFactory extends Factory
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
            'facility_id' => \App\Models\Facility::factory(),
            'ward_name' => fake()->randomElement(['Pediatrics', 'General Ward', 'Maternity', 'Surgical']),
            'bed_number' => fake()->randomElement(['A-101', 'B-205', 'C-310']),
            'admission_type' => fake()->randomElement(['emergency', 'elective', 'urgent', 'transfer']),
            'admission_reason' => fake()->sentence(),
            'admitted_by_user_id' => \App\Models\User::factory(),
            'admitted_at' => now(),
            'status' => 'active',
        ];
    }
}