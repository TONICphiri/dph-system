<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\District;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3: issuer assisted-access flow. Test results never appear in
 * QR content, print views, flash messages or audit details.
 */
class DhpIssuerTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    private function issuer(?Facility $facility = null): User
    {
        $facility ??= Facility::factory()->create();
        District::query()->firstOrCreate(['name' => 'Blantyre'], ['region' => 'Southern']);

        return User::factory()->create(['role' => 'issuer', 'is_active' => true, 'facility_id' => $facility->id]);
    }

    private function citizen(array $overrides = []): Citizen
    {
        return Citizen::factory()->create(array_merge([
            'district' => 'Blantyre',
            'date_of_birth' => '1990-05-04',
        ], $overrides));
    }

    private function confirm(User $issuer, Citizen $citizen): void
    {
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.confirm-identity'), [
            'citizen_id' => $citizen->id,
            'first_name' => $citizen->first_name,
            'last_name' => $citizen->last_name,
        ])->assertRedirect(route('dhp.issuer.citizens.show', $citizen));
    }

    private function vaccinate(User $issuer, Citizen $citizen, array $overrides = []): Credential
    {
        $this->confirm($issuer, $citizen);

        $response = $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), array_merge([
            'type' => 'vaccination',
            'issue_date' => now()->toDateString(),
            'vaccine_name' => 'Measles',
            'dose_number' => 1,
            'administration_date' => now()->subDay()->toDateString(),
        ], $overrides));

        $response->assertRedirect();
        $this->assertStringContainsString('/print', (string) $response->headers->get('Location'));

        return Credential::query()->where('citizen_id', $citizen->id)->latest('id')->firstOrFail();
    }

    public function test_role_gating_and_empty_search_rules(): void
    {
        $issuer = $this->issuer();

        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search'))->assertOk()->assertSee('Search Citizen');

        foreach (['citizen', 'verifier', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]))
                ->get(route('dhp.issuer.citizens.search'))->assertForbidden();
        }

        // Empty query renders the form with no results and no error.
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search'))
            ->assertOk()->assertDontSee('Results (');

        // Name search under 3 characters is rejected.
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search', ['query' => 'Ab']))
            ->assertOk()->assertSee('at least 3 characters');

        // Date-of-birth-only search is rejected.
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search', ['date_of_birth' => '1990-05-04']))
            ->assertOk()->assertSee('together with the date of birth');

        // Search never returns all citizens with an empty query (seed decoys).
        Citizen::factory()->count(3)->create();
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search'))
            ->assertOk()->assertDontSee('Results (');
    }

    public function test_guests_cannot_reach_issuer_search(): void
    {
        $this->get(route('dhp.issuer.citizens.search'))->assertRedirect(route('login'));
        $this->getJson(route('dhp.issuer.citizens.suggestions', ['query' => 'Someone']))->assertUnauthorized();
    }

    public function test_suggestions_are_private_limited_and_issuer_only(): void
    {
        $issuer = $this->issuer();

        for ($i = 0; $i < 12; $i++) {
            $this->citizen(['first_name' => 'Suggestable', 'last_name' => "Family{$i}", 'national_id' => "SUG{$i}9999", 'phone' => "+26599000{$i}00", 'village' => 'SecretVille']);
        }

        $this->actingAs(User::factory()->create(['role' => 'citizen', 'is_active' => true]))
            ->getJson(route('dhp.issuer.citizens.suggestions', ['query' => 'Suggestable']))->assertForbidden();

        $body = $this->actingAs($issuer)->getJson(route('dhp.issuer.citizens.suggestions', ['query' => 'Suggestable']))
            ->assertOk()->json('data');

        $this->assertCount(10, $body);
        $first = $body[0];
        $this->assertArrayHasKey('passport_id', $first);
        $this->assertArrayHasKey('national_id_masked', $first);
        $this->assertArrayHasKey('confirm_url', $first);
        foreach (['national_id', 'email', 'phone', 'village', 'qr_token', 'credentials', 'date_of_birth'] as $banned) {
            $this->assertArrayNotHasKey($banned, $first);
        }
        $this->assertStringEndsWith('99', $first['national_id_masked']);
        $this->assertStringNotContainsString('SUG0', $first['national_id_masked']);
    }

    public function test_registration_creates_passport_and_handles_duplicates(): void
    {
        $issuer = $this->issuer();
        $payload = [
            'first_name' => 'Chikondi', 'last_name' => 'Banda', 'sex' => 'female',
            'date_of_birth' => '1992-02-20', 'district' => 'Blantyre', 'national_id' => 'REGDUP01',
        ];

        $response = $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), $payload);
        $citizen = Citizen::query()->where('national_id', 'REGDUP01')->firstOrFail();
        $this->assertMatchesRegularExpression('/^MW-DHP-\d{4}-\d{6}$/', $citizen->passport_id);
        $response->assertRedirect(route('dhp.issuer.citizens.registration-slip', $citizen));
        $this->assertDatabaseHas('audit_logs', ['action' => 'citizen_registered', 'entity_type' => 'citizen', 'entity_id' => $citizen->id]);

        // Same National ID is rejected without creating a second record.
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), array_merge($payload, ['first_name' => 'Other']))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertSame(1, Citizen::query()->where('national_id', 'REGDUP01')->count());

        // Demographic duplicate warns and links the existing passport.
        $dupPayload = array_merge($payload, ['national_id' => 'BRANDNEW9']);
        unset($dupPayload['national_id']);
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), $dupPayload)
            ->assertRedirect()->assertSessionHas('duplicate');
        $this->assertSame(1, Citizen::query()->where('first_name', 'Chikondi')->count());

        // Explicit documented override creates the second record.
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), $dupPayload + [
            'duplicate_override' => '1', 'duplicate_reason' => 'Twins with identical details confirmed.',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, Citizen::query()->where('first_name', 'Chikondi')->count());
    }

    public function test_registration_portal_and_non_smartphone_paths(): void
    {
        $issuer = $this->issuer();

        // Non-smartphone citizen: no user row, user_id stays null.
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), [
            'first_name' => 'Yamikani', 'last_name' => 'Phiri', 'sex' => 'male',
            'date_of_birth' => '1985-07-11', 'district' => 'Blantyre',
        ])->assertSessionHasNoErrors();
        $plain = Citizen::query()->where('first_name', 'Yamikani')->firstOrFail();
        $this->assertNull($plain->user_id);
        $this->assertSame(0, User::query()->where('name', 'Yamikani Phiri')->count());

        // Explicit portal account: linked citizen user, no password exposed.
        $response = $this->actingAs($issuer)->post(route('dhp.issuer.citizens.store'), [
            'first_name' => 'Tadala', 'last_name' => 'Mvula', 'sex' => 'female',
            'date_of_birth' => '1998-03-03', 'district' => 'Blantyre',
            'email' => 'tadala@example.com', 'create_account' => '1',
        ]);
        $response->assertSessionHasNoErrors();
        $linked = Citizen::query()->where('email', 'tadala@example.com')->firstOrFail();
        $account = User::query()->where('email', 'tadala@example.com')->firstOrFail();
        $this->assertSame('citizen', $account->role->value);
        $this->assertTrue($account->is_active);
        $this->assertSame($account->id, $linked->user_id);
        $response->assertSessionHas('success');
    }

    public function test_registration_slip_masks_national_id(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen(['national_id' => 'SLIP9876']);

        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.registration-slip', $citizen))
            ->assertOk()
            ->assertSee('Digital Health Passport')
            ->assertSee($citizen->passport_id)
            ->assertSee('******76')
            ->assertDontSee('SLIP9876')
            ->assertSee('window.print');
    }

    public function test_identity_confirmation_gate(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen();

        // Profile is unreachable before confirmation.
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.show', $citizen))
            ->assertRedirect(route('dhp.issuer.citizens.search'));

        // One matching field is not enough and writes a safe audit row.
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.confirm-identity'), [
            'citizen_id' => $citizen->id, 'first_name' => $citizen->first_name,
        ])->assertRedirect()->assertSessionHas('error');
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.show', $citizen))
            ->assertRedirect(route('dhp.issuer.citizens.search'));
        $failed = AuditLog::query()->where('action', 'citizen_identity_confirmation_failed')->firstOrFail();
        $this->assertSame(['reason' => 'insufficient_matches'], $failed->details);

        // Two matching fields grant 15-minute access with a safe audit row.
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.show', $citizen))->assertOk()->assertSee($citizen->passport_id);
        $confirmed = AuditLog::query()->where('action', 'citizen_identity_confirmed')->firstOrFail();
        $this->assertSame(['matched_field_count' => 2], $confirmed->details);
    }

    public function test_issue_vaccination_and_lab_credentials(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen();

        $vacc = $this->vaccinate($issuer, $citizen);
        $this->assertMatchesRegularExpression('/^MW-CRED-\d{4}-\d{6}$/', $vacc->credential_number);
        $this->assertSame(64, strlen($vacc->qr_token));
        $this->assertNull($vacc->expiry_date);
        $this->assertSame('Measles', $vacc->vaccinationDetail->vaccine_name);
        $this->assertSame($issuer->id, $vacc->issued_by);
        $this->assertSame($issuer->facility_id, $vacc->facility_id);

        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'lab_test',
            'issue_date' => now()->toDateString(),
            'test_type' => 'Malaria RDT',
            'sample_collection_date' => now()->subDays(2)->toDateString(),
            'result_date' => now()->subDay()->toDateString(),
            'result' => 'Negative',
            'valid_until' => now()->addMonths(6)->toDateString(),
        ])->assertRedirect();
        $lab = Credential::query()->where('citizen_id', $citizen->id)->where('type', 'lab_test')->firstOrFail();
        $this->assertSame($lab->expiry_date->toDateString(), $lab->testDetail->valid_until->toDateString());
        $this->assertNotSame($vacc->credential_number, $lab->credential_number);
        $this->assertNotSame($vacc->qr_token, $lab->qr_token);

        $issued = AuditLog::query()->where('action', 'credential_issued')->latest('id')->firstOrFail();
        $this->assertSame('lab_test', $issued->details['credential_type']);
        $this->assertArrayNotHasKey('result', $issued->details);
    }

    public function test_issuance_validation_and_result_privacy(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen();
        $this->confirm($issuer, $citizen);

        // Missing vaccination fields fail validation.
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination', 'issue_date' => now()->toDateString(),
        ])->assertSessionHasErrors(['vaccine_name', 'dose_number', 'administration_date']);

        // Future issue date fails.
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination', 'issue_date' => now()->addDay()->toDateString(),
            'vaccine_name' => 'BCG', 'dose_number' => 1, 'administration_date' => now()->toDateString(),
        ])->assertSessionHasErrors('issue_date');

        // Result reaches the detail record but never the QR, print view, flash or audit.
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'lab_test',
            'issue_date' => now()->toDateString(),
            'test_type' => 'HIV rapid test',
            'sample_collection_date' => now()->subDays(2)->toDateString(),
            'result_date' => now()->subDay()->toDateString(),
            'result' => 'ResultPrivacyMarker',
            'valid_until' => now()->addMonths(6)->toDateString(),
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Credential issued.');
        $lab = Credential::query()->where('citizen_id', $citizen->id)->where('type', 'lab_test')->firstOrFail();
        $this->assertSame('ResultPrivacyMarker', $lab->testDetail->result);

        $print = $this->actingAs($issuer)->get(route('dhp.issuer.credentials.print', $lab))->assertOk();
        $print->assertDontSee('ResultPrivacyMarker');
        $print->assertSee('/verify/by-token/'.$lab->qr_token, false);
        foreach (AuditLog::query()->where('action', 'credential_issued')->pluck('details') as $details) {
            $this->assertArrayNotHasKey('result', $details);
            $this->assertStringNotContainsString('ResultPrivacyMarker', json_encode($details));
        }
    }

    public function test_correction_preserves_number_and_token(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen();
        $credential = $this->vaccinate($issuer, $citizen);

        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->put(route('dhp.issuer.credentials.update', $credential), [
            'issue_date' => now()->toDateString(),
            'vaccine_name' => 'Measles-Rubella',
            'dose_number' => 2,
            'administration_date' => now()->subDay()->toDateString(),
            'batch_number' => 'BATCH-7',
        ])->assertRedirect();

        $credential->refresh();
        $this->assertSame('Measles-Rubella', $credential->vaccinationDetail->vaccine_name);
        $this->assertSame('BATCH-7', $credential->vaccinationDetail->batch_number);
        $updated = AuditLog::query()->where('action', 'credential_updated')->firstOrFail();
        $this->assertContains('vaccine_name', $updated->details['changed_fields']);

        // Revoked credentials cannot be edited.
        $credential->update(['status' => CredentialStatus::Revoked]);
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->get(route('dhp.issuer.credentials.edit', $credential))->assertForbidden();
    }

    public function test_revoke_and_replace_flow(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen();
        $credential = $this->vaccinate($issuer, $citizen);

        // Reason is mandatory.
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.revoke', $credential), [])
            ->assertSessionHasErrors('reason');

        // Revoke only.
        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.revoke', $credential), ['reason' => 'duplicate'])
            ->assertRedirect(route('dhp.issuer.citizens.show', $citizen));
        $credential->refresh();
        $this->assertSame(CredentialStatus::Revoked, $credential->status);
        $this->assertSame($issuer->id, $credential->revoked_by);
        $this->assertNotNull($credential->revoked_at);
        $revoked = AuditLog::query()->where('action', 'credential_revoked')->firstOrFail();
        $this->assertSame('duplicate', $revoked->details['reason_category']);

        // Revoke and replace links a distinct new credential.
        $second = $this->vaccinate($issuer, $citizen);
        $this->confirm($issuer, $citizen);
        $response = $this->actingAs($issuer)->post(route('dhp.issuer.credentials.revoke', $second), [
            'reason' => 'data_entry_error', 'replace' => '1',
        ]);
        $response->assertRedirect(route('dhp.issuer.credentials.create', ['citizen' => $citizen->id, 'replace_of' => $second->id]));
        $second->refresh();
        $this->assertSame(CredentialStatus::Superseded, $second->status);

        $this->confirm($issuer, $citizen);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination',
            'issue_date' => now()->toDateString(),
            'vaccine_name' => 'Measles',
            'dose_number' => 1,
            'administration_date' => now()->subDay()->toDateString(),
            'replace_of' => $second->id,
        ])->assertRedirect();
        $replacement = Credential::query()->where('citizen_id', $citizen->id)->latest('id')->firstOrFail();
        $this->assertNotSame($second->credential_number, $replacement->credential_number);
        $this->assertNotSame($second->qr_token, $replacement->qr_token);
        $this->assertSame($replacement->id, $second->fresh()->replaced_by_credential_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'credential_replaced']);
    }

    public function test_certificate_contents(): void
    {
        $issuer = $this->issuer();
        $citizen = $this->citizen(['national_id' => 'CERT1234', 'phone' => '+265991000001', 'village' => 'HiddenVille']);
        $credential = $this->vaccinate($issuer, $citizen, ['batch_number' => 'BATCH-SECRET']);

        $this->confirm($issuer, $citizen);
        $response = $this->actingAs($issuer)->get(route('dhp.issuer.credentials.print', $credential))->assertOk();

        foreach ([$citizen->full_name, $citizen->passport_id, $credential->credential_number, 'No expiry recorded', '/verify/by-token/'.$credential->qr_token] as $visible) {
            $response->assertSee($visible, false);
        }
        $response->assertSee('window.print', false);

        foreach (['CERT1234', '+265991000001', 'HiddenVille', 'BATCH-SECRET'] as $hidden) {
            $response->assertDontSee($hidden, false);
        }
        $response->assertDontSee($citizen->date_of_birth->format('j M Y'), false);
    }
}
