<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Credential;
use App\Models\Facility;
use App\Models\TestDetail;
use App\Models\User;
use App\Models\VaccinationDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        $year = now()->format('Y');

        return [
            'credential_number' => 'MW-CRED-'.$year.'-'.fake()->unique()->numerify('######'),
            'citizen_id' => Citizen::factory(),
            'facility_id' => Facility::factory(),
            'type' => 'vaccination',
            'status' => 'active',
            'issue_date' => now()->toDateString(),
            'expiry_date' => null,
            // QR tokens are random and opaque, NO personal data.
            'qr_token' => Str::random(64),
            'issued_by' => User::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Credential $credential) {
            if ($credential->type->value === 'lab_test') {
                TestDetail::factory()->create(['credential_id' => $credential->id]);
            } else {
                VaccinationDetail::factory()->create(['credential_id' => $credential->id]);
            }
        });
    }

    public function labTest(): static
    {
        return $this->state(fn () => [
            'type' => 'lab_test',
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'expiry_date' => now()->subDay()->toDateString(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => 'Entered in error.',
        ]);
    }
}
