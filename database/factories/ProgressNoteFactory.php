<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgressNote>
 */
class ProgressNoteFactory extends Factory
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
            'recorded_by_user_id' => \App\Models\User::factory(),
            'note' => fake()->sentence(12),
            'recorded_at' => now(),
        ];
    }
}