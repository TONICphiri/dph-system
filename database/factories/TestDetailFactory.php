<?php

namespace Database\Factories;

use App\Models\TestDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestDetail>
 */
class TestDetailFactory extends Factory
{
    protected $model = TestDetail::class;

    public function definition(): array
    {
        return [
            'credential_id' => \App\Models\Credential::factory()->labTest(),
            'test_type' => fake()->randomElement(['HIV rapid test', 'Malaria RDT', 'TB GeneXpert', 'Hepatitis B']),
            'sample_collection_date' => now()->subDays(7)->toDateString(),
            'result_date' => now()->subDays(6)->toDateString(),
            'result' => fake()->randomElement(['Negative', 'Positive', 'Inconclusive']),
            'valid_until' => now()->addMonths(6)->toDateString(),
        ];
    }
}
