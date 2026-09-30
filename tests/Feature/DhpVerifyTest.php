<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\Facility;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Phase 5: public and authenticated verification disclose minimal data
 * and audit every attempt. Verifier sees minimal data.
 */
class DhpVerifyTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    private function credential(array $overrides = []): Credential
    {
        $citizen = Citizen::factory()->create([
            'first_name' => 'Chikondi',
            'last_name' => 'Banda',
            'national_id' => 'VERIFY01',
            'date_of_birth' => '1990-01-15',
            'email' => 'verify.citizen@example.com',
            'phone' => '+265994445566',
            'village' => 'HiddenVille',
            'district' => 'Blantyre',
        ]);
        $facility = Facility::factory()->create(['name' => 'Verify Health Centre', 'is_active' => true]);

        return Credential::factory()->create(array_merge([
            'citizen_id' => $citizen->id,
            'facility_id' => $facility->id,
        ], $overrides));
    }

    public function test_public_verify_page_has_scanner_manual_and_privacy_notice(): void
    {
        $response = $this->get(route('dhp.verify.index'))->assertOk();

        $response->assertSee('Verify Certificate');
        $response->assertSee('Do not submit National ID numbers or personal medical information', false);
        $response->assertSee('qr-reader');
        $response->assertSee('credential_number');
        // Locally bundled scanner (no CDN): the page wires it with the
        // expected application path, and the shipped script only follows
        // same-origin verify URLs (checked at its committed source).
        $response->assertSee('verify-scanner', false);
        $response->assertSee('/verify/by-token/', false);
        $response->assertSee('dhpVerifyScannerInit', false);

        $scannerSource = file_get_contents(base_path('resources/js/verify-scanner.js'));
        $this->assertStringContainsString('window.location.origin', $scannerSource);
        $this->assertStringContainsString('is not a Digital Health Passport credential', $scannerSource);
        $this->assertStringContainsString('options.buildUrl(token)', $scannerSource);
    }

    public function test_valid_token_shows_only_masked_data(): void
    {
        $credential = $this->credential();

        $response = $this->get(route('dhp.verify.by-token', $credential->qr_token))->assertOk();

        $response->assertSee('Valid');
        $response->assertSee('active and was issued by an authorized facility');
        $response->assertSee('Ch*** Ba***');
        $response->assertSee('***'.substr($credential->citizen->passport_id, -3));
        $response->assertSee('Verify Health Centre');
        $response->assertSee('based on the credential status and issuing facility', false);

        // Never disclosed.
        $vaccineName = $credential->vaccinationDetail->vaccine_name;
        foreach (['Chikondi Banda', $credential->citizen->passport_id, 'VERIFY01', '1990',
            'verify.citizen@example.com', '+265994445566', 'HiddenVille', 'Blantyre',
            $credential->credential_number, $credential->qr_token, $vaccineName] as $hidden) {
            $response->assertDontSee($hidden, false);
        }

        $this->assertDatabaseHas('verifications', [
            'credential_id' => $credential->id, 'verifier_id' => null,
            'method' => 'qr_scan', 'result' => 'valid',
        ]);
        $audit = AuditLog::query()->where('action', 'credential_verified')->firstOrFail();
        $this->assertSame(['method' => 'qr_scan', 'result' => 'valid', 'authenticated_verifier' => false], $audit->details);
    }

    public function test_valid_number_returns_valid_with_masked_data(): void
    {
        $credential = $this->credential();

        $this->post(route('dhp.verify.by-number'), ['credential_number' => strtolower($credential->credential_number)])
            ->assertOk()->assertSee('Valid');

        $this->assertDatabaseHas('verifications', [
            'credential_id' => $credential->id, 'method' => 'manual_code', 'result' => 'valid',
        ]);
    }

    public function test_non_valid_results_hide_all_details(): void
    {
        $verificationsBefore = Verification::query()->count();
        $auditsBefore = AuditLog::query()->where('action', 'credential_verified')->count();

        $expired = $this->credential();
        Credential::factory()->expired()->create(['citizen_id' => $expired->citizen_id, 'facility_id' => $expired->facility_id]);
        $revoked = Credential::factory()->revoked()->create();
        $replaced = Credential::factory()->create(['status' => 'superseded']);

        $this->get(route('dhp.verify.by-token', Credential::query()->where('expiry_date', '<', now())->firstOrFail()->qr_token))
            ->assertOk()->assertSee('Expired')->assertDontSee('Verify Health Centre')->assertDontSee('Ch***');
        $this->get(route('dhp.verify.by-token', $revoked->qr_token))
            ->assertOk()->assertSee('Revoked')->assertDontSee('Verify Health Centre');
        $this->get(route('dhp.verify.by-token', $replaced->qr_token))
            ->assertOk()->assertSee('Replaced')->assertDontSee('Verify Health Centre');

        $this->post(route('dhp.verify.by-number'), ['credential_number' => 'MW-CRED-2099-000000'])
            ->assertOk()->assertSee('could not be verified')->assertDontSee('Verify Health Centre');
        $this->get(route('dhp.verify.by-token', 'short'))->assertOk()->assertSee('could not be verified');
        $this->get(route('dhp.verify.by-token', str_repeat('z', 64)))->assertOk()->assertSee('could not be verified');

        $this->assertSame($verificationsBefore + 6, Verification::query()->count());
        $this->assertSame($auditsBefore + 6, AuditLog::query()->where('action', 'credential_verified')->count());
    }

    public function test_inactive_facility_returns_invalid(): void
    {
        $dead = Facility::factory()->create(['name' => 'Closed Clinic', 'is_active' => false]);
        $credential = Credential::factory()->create(['facility_id' => $dead->id]);

        $this->get(route('dhp.verify.by-token', $credential->qr_token))
            ->assertOk()->assertSee('could not be verified')->assertDontSee('Closed Clinic');

        $this->assertDatabaseHas('verifications', ['credential_id' => $credential->id, 'result' => 'invalid']);
    }

    public function test_authenticated_verifier_sees_fuller_valid_data(): void
    {
        $credential = $this->credential();
        $verifier = User::factory()->create(['role' => 'verifier', 'is_active' => true]);

        $this->actingAs($verifier)->get(route('dhp.verifier.dashboard'))->assertOk()->assertSee('Verify Certificate');
        $this->actingAs($verifier)->get(route('dhp.verifier.verify'))->assertOk()->assertSee('credential_number');

        $response = $this->actingAs($verifier)->get(route('dhp.verifier.by-token', $credential->qr_token))->assertOk();
        $response->assertSee('Valid');
        $response->assertSee('Chikondi Banda');
        $response->assertSee($credential->citizen->passport_id);
        $batch = $credential->vaccinationDetail->batch_number;
        foreach (array_filter(['VERIFY01', 'verify.citizen@example.com', $batch]) as $hidden) {
            $response->assertDontSee($hidden, false);
        }

        $this->assertDatabaseHas('verifications', ['credential_id' => $credential->id, 'verifier_id' => $verifier->id, 'result' => 'valid']);
        $audit = AuditLog::query()->where('action', 'credential_verified')->firstOrFail();
        $this->assertTrue($audit->details['authenticated_verifier']);

        // Other DHP roles are denied the verifier portal.
        foreach (['citizen', 'issuer', 'admin'] as $role) {
            $other = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($other)->get(route('dhp.verifier.dashboard'))->assertForbidden();
            $this->actingAs($other)->get(route('dhp.verifier.by-token', $credential->qr_token))->assertForbidden();
        }
    }

    public function test_public_verification_is_throttled(): void
    {
        $credential = $this->credential();

        // Other tests share the throttled IP key; start from a clean count.
        RateLimiter::clear('127.0.0.1');

        for ($attempt = 1; $attempt <= 31; $attempt++) {
            $response = $this->get(route('dhp.verify.by-token', $credential->qr_token));

            if ($attempt <= 30) {
                $response->assertOk();
            } else {
                $response->assertStatus(429);
            }
        }
    }
}
