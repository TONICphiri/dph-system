<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4: read-only citizen passport. Citizens see only their own
 * credentials, never results, batches, contacts or hospital data.
 */
class DhpCitizenPortalTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    /**
     * @return array{0: User, 1: Citizen}
     */
    private function citizenAccount(array $citizenOverrides = []): array
    {
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $citizen = Citizen::factory()->create(array_merge([
            'user_id' => $user->id,
            'national_id' => 'PORTAL01',
            'email' => 'portal.citizen@example.com',
            'phone' => '+265991112233',
            'village' => 'PrivateVille',
        ], $citizenOverrides));

        return [$user, $citizen];
    }

    public function test_dashboard_shows_only_own_credentials_and_no_sensitive_data(): void
    {
        [$user, $citizen] = $this->citizenAccount();
        [$otherUser, $otherCitizen] = $this->citizenAccount(['national_id' => 'PORTAL02', 'email' => 'other@example.com', 'phone' => '+265992223344']);

        $facility = \App\Models\Facility::factory()->create(['name' => 'Ndirande Health Centre']);
        $own = Credential::factory()->create(['citizen_id' => $citizen->id, 'facility_id' => $facility->id]);
        Credential::factory()->create(['citizen_id' => $otherCitizen->id, 'facility_id' => $facility->id]);

        $response = $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk();

        $response->assertSee('My Passport');
        $response->assertSee($citizen->full_name);
        $response->assertSee($citizen->passport_id);
        $response->assertSee($own->credential_number);
        $response->assertSee('verified health credentials');

        // Another citizen's credential number never appears.
        $otherNumber = Credential::query()->where('citizen_id', $otherCitizen->id)->value('credential_number');
        $response->assertDontSee($otherNumber);

        // Sensitive and legacy content never appears.
        foreach (['PORTAL01', 'portal.citizen@example.com', '+265991112233', 'PrivateVille',
            $citizen->date_of_birth->format('j M Y'), $own->qr_token,
            'patient', 'appointment', 'prescription', 'diagnosis', 'ward'] as $hidden) {
            $response->assertDontSee($hidden, false);
        }
    }

    public function test_dashboard_empty_state_and_status_grouping(): void
    {
        [$user, $citizen] = $this->citizenAccount();

        $this->actingAs($user)->get(route('dhp.citizen.dashboard'))
            ->assertOk()->assertSee('No health credentials are available in your passport yet.');

        Credential::factory()->create(['citizen_id' => $citizen->id]);
        Credential::factory()->expired()->create(['citizen_id' => $citizen->id]);
        Credential::factory()->revoked()->create(['citizen_id' => $citizen->id]);
        Credential::factory()->create(['citizen_id' => $citizen->id, 'status' => 'superseded']);

        $response = $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk();
        foreach (['Active', 'Expired', 'Revoked', 'Replaced'] as $heading) {
            $response->assertSee($heading);
        }
    }

    public function test_citizen_views_own_vaccination_credential(): void
    {
        [$user, $citizen] = $this->citizenAccount();
        $credential = Credential::factory()->create(['citizen_id' => $citizen->id]);
        $credential->vaccinationDetail->update(['vaccine_name' => 'BCGVaccine', 'batch_number' => 'BATCH-HIDDEN']);

        $response = $this->actingAs($user)->get(route('dhp.citizen.credentials.show', $credential))->assertOk();
        $response->assertSee('BCGVaccine');
        $response->assertSee('This credential is active and can be presented');
        $response->assertDontSee('BATCH-HIDDEN');
        $response->assertDontSee($credential->qr_token);

        $viewed = AuditLog::query()->where('action', 'citizen_credential_viewed')->firstOrFail();
        $this->assertSame(['credential_type' => 'vaccination', 'credential_status' => 'active'], $viewed->details);
    }

    public function test_citizen_lab_detail_hides_result_and_test_details(): void
    {
        [$user, $citizen] = $this->citizenAccount();
        $credential = Credential::factory()->labTest()->create(['citizen_id' => $citizen->id]);
        $credential->testDetail->update(['test_type' => 'SecretTestType', 'result' => 'SecretResultValue']);

        $response = $this->actingAs($user)->get(route('dhp.citizen.credentials.show', $credential))->assertOk();
        $response->assertSee('Laboratory credential issued');
        $response->assertDontSee('SecretTestType');
        $response->assertDontSee('SecretResultValue');
    }

    public function test_citizen_cannot_open_other_citizens_credentials(): void
    {
        [$user] = $this->citizenAccount();
        [$otherUser, $otherCitizen] = $this->citizenAccount(['national_id' => 'PORTAL03', 'email' => 'third@example.com', 'phone' => '+265993334455']);
        $foreign = Credential::factory()->create(['citizen_id' => $otherCitizen->id]);

        $this->actingAs($user)->get(route('dhp.citizen.credentials.show', $foreign))->assertForbidden();
        $this->actingAs($user)->get(route('dhp.citizen.credentials.print', $foreign))->assertForbidden();

        foreach (['issuer', 'verifier', 'admin'] as $role) {
            $staff = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($staff)->get(route('dhp.citizen.dashboard'))->assertForbidden();
            $this->actingAs($staff)->get(route('dhp.citizen.credentials.show', $foreign))->assertForbidden();
        }
    }

    public function test_qr_rules_for_active_and_inactive_credentials(): void
    {
        [$user, $citizen] = $this->citizenAccount();
        $active = Credential::factory()->create(['citizen_id' => $citizen->id]);
        $revoked = Credential::factory()->revoked()->create(['citizen_id' => $citizen->id]);

        // Dashboard: QR toggle only on the active card.
        $dashboard = $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk();
        $dashboard->assertSee('Show QR');
        $this->assertSame(1, substr_count($dashboard->getContent(), 'data-qr-toggle='));

        // Detail: QR toggle only when active.
        $this->actingAs($user)->get(route('dhp.citizen.credentials.show', $active))->assertOk()->assertSee('Show QR');
        $this->actingAs($user)->get(route('dhp.citizen.credentials.show', $revoked))->assertOk()
            ->assertDontSee('data-qr-toggle=')
            ->assertSee('revoked and must not be used');
    }

    public function test_citizen_print_pages(): void
    {
        [$user, $citizen] = $this->citizenAccount(['national_id' => 'PRINT01']);
        $active = Credential::factory()->create(['citizen_id' => $citizen->id]);
        $expired = Credential::factory()->expired()->create(['citizen_id' => $citizen->id]);

        $print = $this->actingAs($user)->get(route('dhp.citizen.credentials.print', $active))->assertOk();
        foreach ([$citizen->full_name, $citizen->passport_id, $active->credential_number, 'window.print'] as $visible) {
            $print->assertSee($visible, false);
        }
        $print->assertSee('<svg', false);
        foreach (['PRINT01', 'portal.citizen@example.com', 'PrivateVille', $active->qr_token,
            $citizen->date_of_birth->format('j M Y')] as $hidden) {
            $print->assertDontSee($hidden, false);
        }

        $stale = $this->actingAs($user)->get(route('dhp.citizen.credentials.print', $expired))->assertOk();
        $stale->assertSee('must not be used');
        $stale->assertDontSee('<svg', false);

        $printed = AuditLog::query()->where('action', 'citizen_certificate_printed')->firstOrFail();
        $this->assertSame('vaccination', $printed->details['credential_type']);
        $this->assertArrayNotHasKey('qr_token', $printed->details);
    }
}
