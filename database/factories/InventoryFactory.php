<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
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
            'medication_name' => fake()->randomElement(['Paracetamol', 'Amoxicillin', 'ORS Sachets', 'Coartem', 'Ibuprofen']),
            'medication_code' => strtoupper(fake()->unique()->bothify('MED-####')),
            'strength' => fake()->randomElement(['500mg', '250mg', '1g', '10ml']),
            'current_stock' => fake()->numberBetween(0, 100),
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_of_measurement' => 'tablets',
            'status' => 'available',
            'last_restocked_at' => now(),
        ];
    }

    /**
     * Indicate the item is low on stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 5,
            'status' => 'low_stock',
        ]);
    }

    /**
     * Indicate the item is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => 0,
            'status' => 'out_of_stock',
        ]);
    }
}