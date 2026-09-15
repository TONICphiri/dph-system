<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class PatientRecordsTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function makePatientUser(Patient $file, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'status' => 'active',
            'must_change_password' => false,
            'patient_id' => $file->id,
        ], $overrides));
        $user->assignRole('patient');

        return $user;
    }

    protected function enableTwoFactor(User $user): string
    {
        $secret = TwoFactorService::generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->saveQuietly();

        return $secret;
    }

    protected function passChallenge(): void
    {
        TwoFactorService::markSessionVerified();
    }

    public function test_totp_matches_rfc_vector(): void
    {
        // RFC 6238: secret "12345678901234567890", T=59s -> 287082 (6-digit).
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', TwoFactorService::currentCode($secret, 59));
        $this->assertTrue(TwoFactorService::verify($secret, '287082', 59));
        $this->assertFalse(TwoFactorService::verify($secret, '123456', 59));
    }

    public function test_patient_views_own_records_after_2fa(): void
    {
        $file = Patient::factory()->create(['first_name' => 'Amina', 'last_name' => 'Yusuf']);
        $user = $this->makePatientUser($file);
        $this->enableTwoFactor($user);

        // No 2FA session yet: sent to the challenge.
        $this->actingAs($user)->get('/patient/records')->assertRedirect(route('two-factor.challenge'));
        $this->actingAs($user)->get("/patients/{$file->id}")->assertRedirect(route('two-factor.challenge'));

        $this->passChallenge();

        $this->actingAs($user)->get('/patient/records')
            ->assertStatus(200)
            ->assertSee('Amina Yusuf', false);

        $this->actingAs($user)->get("/patients/{$file->id}")->assertStatus(200);
    }

    public function test_patient_cannot_open_another_patients_file(): void
    {
        $mine = Patient::factory()->create();
        $theirs = Patient::factory()->create();
        $user = $this->makePatientUser($mine);
        $this->enableTwoFactor($user);
        $this->passChallenge();

        $this->actingAs($user)->get("/patients/{$theirs->id}")->assertForbidden();
        $this->actingAs($user)->get("/patients/{$theirs->id}/qr-code")->assertForbidden();
        $this->actingAs($user)->get("/lab/orders/patient/{$theirs->id}")->assertForbidden();
    }

    public function test_patient_without_2fa_is_sent_to_setup(): void
    {
        $file = Patient::factory()->create();
        $user = $this->makePatientUser($file);

        $this->actingAs($user)->get('/patient/records')->assertRedirect(route('settings.2fa'));
    }

    public function test_patient_without_linked_file_sees_empty_state(): void
    {
        $user = User::factory()->create(['status' => 'active', 'must_change_password' => false]);
        $user->assignRole('patient');
        $this->enableTwoFactor($user);
        $this->passChallenge();

        $this->actingAs($user)->get('/patient/records')
            ->assertStatus(200)
            ->assertSee('No file linked yet', false);
    }

    public function test_two_factor_setup_and_challenge_flow(): void
    {
        $file = Patient::factory()->create();
        $user = $this->makePatientUser($file);

        // Setup page shows a QR + manual key.
        $this->actingAs($user)->get('/settings/2fa')->assertStatus(200);

        // Wrong code rejected.
        $this->actingAs($user)->post(route('settings.2fa.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        // Right code (from the pending secret) switches 2FA on + shows recovery codes once.
        $secret = session('two_factor_pending_secret');
        $this->assertNotEmpty($secret);
        $confirm = $this->actingAs($user)->post(route('settings.2fa.confirm'), [
            'code' => TwoFactorService::currentCode($secret),
        ]);
        $confirm->assertRedirect(route('settings.2fa.recovery'));
        $confirm->assertSessionHas('recovery_codes');
        $shownCodes = session('recovery_codes');
        $this->assertCount(10, $shownCodes);

        $this->assertTrue($user->fresh()->hasTwoFactor());
        $this->assertSame(10, TwoFactorService::remainingRecoveryCodes($user->fresh()));

        $this->actingAs($user)->get(route('settings.2fa.recovery'))
            ->assertStatus(200)
            ->assertSee($shownCodes[0], false);

        // New session without verification: challenge required, then passes.
        TwoFactorService::clearSession();
        $this->actingAs($user)->get('/patient/records')->assertRedirect(route('two-factor.challenge'));
        $this->actingAs($user)->get('/two-factor-challenge')->assertStatus(200);
        $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => TwoFactorService::currentCode($user->fresh()->two_factor_secret),
        ])->assertRedirect(route('patient.records'));
        $this->actingAs($user)->get('/patient/records')->assertStatus(200);
    }

    public function test_recovery_code_works_once_then_burns(): void
    {
        $file = Patient::factory()->create();
        $user = $this->makePatientUser($file);
        $secret = $this->enableTwoFactor($user);
        $codes = TwoFactorService::generateRecoveryCodes(2);
        $user->forceFill(['two_factor_recovery_codes' => TwoFactorService::hashRecoveryCodes($codes)])->saveQuietly();

        // First use passes and burns the code.
        $this->actingAs($user)->post(route('two-factor.verify'), ['code' => $codes[0]])
            ->assertRedirect(route('patient.records'));
        $this->assertSame(1, TwoFactorService::remainingRecoveryCodes($user->fresh()));

        // Same code again is rejected.
        TwoFactorService::clearSession();
        $this->actingAs($user)->post(route('two-factor.verify'), ['code' => $codes[0]])
            ->assertSessionHasErrors('code');

        // Plaintext codes are never stored.
        $this->assertStringNotContainsString($codes[1], (string) $user->fresh()->two_factor_recovery_codes);
    }

    public function test_challenge_locks_out_brute_force(): void
    {
        $file = Patient::factory()->create();
        $user = $this->makePatientUser($file);
        $secret = $this->enableTwoFactor($user);

        // Find a guaranteed-wrong code for right now.
        $wrong = '000000';
        if (TwoFactorService::verify($secret, $wrong)) {
            $wrong = '111111';
        }

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('two-factor.verify'), ['code' => $wrong])
                ->assertSessionHasErrors('code');
        }

        // Sixth try: locked, even with the RIGHT code.
        $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => TwoFactorService::currentCode($secret),
        ])->assertSessionHasErrors('code');

        $this->assertGuestToPatientRecords($user);
    }

    protected function assertGuestToPatientRecords(User $user): void
    {
        // Still unverified: records stay closed.
        TwoFactorService::clearSession();
        $this->actingAs($user)->get('/patient/records')->assertRedirect(route('two-factor.challenge'));
    }

    public function test_staff_access_unchanged(): void
    {
        $facility = Facility::factory()->create();
        $doctor = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $doctor->assignRole('doctor');
        $file = Patient::factory()->create();

        // Staff open any file with no 2FA involved.
        $this->actingAs($doctor)->get("/patients/{$file->id}")->assertStatus(200);
        $this->actingAs($doctor)->get('/patient/records')->assertStatus(200);
    }

    public function test_staff_can_link_account_to_file_by_dhp_id(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $admin->assignRole('facility_admin');
        $account = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $account->assignRole('patient');
        $file = Patient::factory()->create();

        $this->actingAs($admin)->post(route('facility.users.link-file', $account), [
            'dhp_id' => $file->dhp_id,
        ])->assertRedirect();

        $this->assertSame($file->id, (int) $account->fresh()->patient_id);
    }
}
