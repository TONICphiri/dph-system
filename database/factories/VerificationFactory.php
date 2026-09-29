<?php

namespace Database\Factories;

use App\Models\Verification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verification>
 */
class VerificationFactory extends Factory
{
    protected $model = Verification::class;

    public function definition(): array
    {
        return [
            'credential_id' => null,
            'verifier_id' => null,
            'method' => fake()->randomElement(['qr_scan', 'manual_code']),
            'result' => 'valid',
            'verified_at' => now(),
            'ip_address' => '127.0.0.1',
        ];
    }
}
