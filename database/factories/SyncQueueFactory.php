<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncQueue>
 */
class SyncQueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'facility_id' => \App\Models\Facility::factory(),
            'record_type' => 'patients',
            'record_id' => 1,
            'action' => 'create',
            'payload' => ['id' => 1, 'dhp_id' => fake()->unique()->bothify('DHP-####-####')],
            'status' => 'pending',
            'retry_count' => 0,
        ];
    }

    /**
     * Indicate the record has already been synced.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    /**
     * Indicate the record failed to sync.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Server rejected sync payload',
            'retry_count' => 1,
        ]);
    }
}