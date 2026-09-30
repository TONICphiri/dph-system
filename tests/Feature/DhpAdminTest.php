<?php

namespace Tests\Feature;

use App\Enums\CredentialStatus;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\Facility;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: DHP administration and credential expiry.
 */
class DhpAdminTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_routes_are_admin_only(): void
    {
        $urls = [
            route('dhp.admin.dashboard'),
            route('dhp.admin.users.index'),
            route('dhp.admin.users.create'),
            route('dhp.admin.facilities.index'),
            route('dhp.admin.facilities.create'),
            route('dhp.admin.audit-logs.index'),
        ];

        foreach ($urls as $url) {
            $this->actingAs($this->admin())->get($url)->assertOk();

            foreach (['citizen', 'issuer', 'verifier'] as $role) {
                $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]))
                    ->get($url)->assertForbidden();
            }
        }
    }

    public function test_guests_cannot_reach_admin_routes(): void
    {
        foreach (['dhp.admin.dashboard', 'dhp.admin.users.index', 'dhp.admin.facilities.index', 'dhp.admin.audit-logs.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_dashboard_counts_and_excludes_sensitive_fields(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create(['role' => 'issuer', 'is_active' => true]);
        $citizen = Citizen::factory()->create();
        Credential::factory()->create(['citizen_id' => $citizen->id]);
        Credential::factory()->revoked()->create(['citizen_id' => $citizen->id]);

        $response = $this->actingAs($admin)->get(route('dhp.admin.dashboard'))->assertOk();
        $response->assertSee('>Issuers</dt><dd class="text-xl font-semibold">2</dd>', false);
        $response->assertSee('>Administrators</dt><dd class="text-xl font-semibold">1</dd>', false);
        $response->assertSee('>Revoked</dt><dd class="text-xl font-semibold">1</dd>', false);
        $response->assertDontSee('{', false);
        $response->assertDontSee($citizen->passport_id);
    }

    public function test_user_list_pagination_and_privacy(): void
    {
        $admin = $this->admin();
        User::factory()->count(21)->create(['role' => 'verifier', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('dhp.admin.users.index'))->assertOk();
        $response->assertSee('page=2', false);
        $response->assertDontSee('remember_token');
        $response->assertDontSee('job_title');
    }

    public function test_admin_creates_staff_with_email_and_rejects_citizen_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dhp.admin.users.store'), [
            'name' => 'New Issuer', 'email' => 'new.issuer@example.com', 'role' => 'issuer', 'is_active' => '1',
        ])->assertRedirect(route('dhp.admin.users.index'))->assertSessionHas('success');

        $user = User::query()->where('email', 'new.issuer@example.com')->firstOrFail();
        $this->assertSame('issuer', $user->role->value);
        $this->assertTrue($user->must_change_password);
        $audit = AuditLog::query()->where('action', 'dhp_user_created')->firstOrFail();
        $this->assertSame(['role' => 'issuer', 'is_active' => true], $audit->details);

        $this->actingAs($admin)->post(route('dhp.admin.users.store'), [
            'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'role' => 'citizen',
        ])->assertSessionHasErrors('role');
        $this->assertNull(User::query()->where('email', 'sneaky@example.com')->first());
    }

    public function test_role_change_takes_effect_and_final_admin_is_protected(): void
    {
        $admin = $this->admin();
        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);

        $this->actingAs($admin)->put(route('dhp.admin.users.update', $issuer), [
            'name' => $issuer->name, 'email' => $issuer->email, 'role' => 'verifier', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertSame('verifier', $issuer->fresh()->role->value);
        $this->actingAs($issuer->fresh())->get(route('dhp.verifier.dashboard'))->assertOk();
        $this->actingAs($issuer->fresh())->get(route('dhp.issuer.dashboard'))->assertForbidden();

        // Final active admin cannot be deactivated.
        $this->actingAs($admin)->post(route('dhp.admin.users.toggle-active', $admin))
            ->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_linked_citizen_account_role_is_locked(): void
    {
        $admin = $this->admin();
        $citizenUser = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        Citizen::factory()->create(['user_id' => $citizenUser->id]);

        $this->actingAs($admin)->put(route('dhp.admin.users.update', $citizenUser), [
            'name' => $citizenUser->name, 'email' => $citizenUser->email, 'role' => 'issuer', 'is_active' => '1',
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertSame('citizen', $citizenUser->fresh()->role->value);
    }

    public function test_deactivated_user_loses_access_immediately(): void
    {
        $admin = $this->admin();
        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);

        $this->actingAs($admin)->post(route('dhp.admin.users.toggle-active', $issuer))->assertRedirect();
        $this->assertFalse($issuer->fresh()->is_active);
        $this->actingAs($issuer->fresh())->get(route('dhp.issuer.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'dhp_user_deactivated']);
    }

    public function test_facility_crud_filtering_and_safe_audits(): void
    {
        $admin = $this->admin();
        $lab = Facility::factory()->create(['name' => 'Filter Lab One', 'type' => 'laboratory', 'is_active' => true]);
        Facility::factory()->create(['name' => 'Filter Clinic Two', 'type' => 'health_centre', 'is_active' => true]);

        // Filter by type.
        $response = $this->actingAs($admin)->get(route('dhp.admin.facilities.index', ['type' => 'laboratory']))->assertOk();
        $response->assertSee('Filter Lab One');
        $response->assertDontSee('Filter Clinic Two');

        // Create + deactivate + reactivate.
        $this->actingAs($admin)->post(route('dhp.admin.facilities.store'), [
            'name' => 'New Health Centre', 'district' => 'Blantyre', 'type' => 'health_centre', 'is_active' => '1',
        ])->assertRedirect();
        $created = Facility::query()->where('name', 'New Health Centre')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['action' => 'facility_created']);

        $this->actingAs($admin)->post(route('dhp.admin.facilities.toggle-active', $created))->assertRedirect();
        $this->assertFalse($created->fresh()->is_active);
        $this->actingAs($admin)->post(route('dhp.admin.facilities.toggle-active', $created))->assertRedirect();
        $this->assertTrue($created->fresh()->is_active);

        $deactivated = AuditLog::query()->where('action', 'facility_deactivated')->firstOrFail();
        $this->assertSame(['facility_type' => 'health_centre', 'is_active' => false], $deactivated->details);
        $this->assertStringNotContainsString('New Health Centre', json_encode($deactivated->details));
    }

    public function test_inactive_facility_blocks_issuance_and_verification(): void
    {
        $admin = $this->admin();
        $facility = Facility::factory()->create(['is_active' => true]);
        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true, 'facility_id' => $facility->id]);
        $citizen = Citizen::factory()->create();

        $credential = Credential::factory()->create(['citizen_id' => $citizen->id, 'facility_id' => $facility->id]);
        $this->get(route('dhp.verify.by-token', $credential->qr_token))->assertOk()->assertSee('Valid');

        $this->actingAs($admin)->post(route('dhp.admin.facilities.toggle-active', $facility))->assertRedirect();

        $this->get(route('dhp.verify.by-token', $credential->qr_token))->assertOk()
            ->assertSee('could not be verified')->assertDontSee($facility->name);

        // Issuance with an explicitly inactive facility is rejected.
        $issuer->update(['facility_id' => null]);
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.confirm-identity'), [
            'citizen_id' => $citizen->id, 'first_name' => $citizen->first_name, 'last_name' => $citizen->last_name,
        ]);
        $this->actingAs($issuer)->post(route('dhp.issuer.credentials.store', $citizen), [
            'type' => 'vaccination', 'facility_id' => $facility->id, 'issue_date' => now()->toDateString(),
            'vaccine_name' => 'BCG', 'dose_number' => 1, 'administration_date' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_audit_viewer_filters_masks_and_whitelists(): void
    {
        $admin = $this->admin();
        AuditLog::create([
            'user_id' => $admin->id, 'action' => 'credential_verified', 'entity_type' => 'credential',
            'entity_id' => 7, 'description' => 'credential_verified',
            'details' => ['method' => 'qr_scan', 'result' => 'valid', 'secret_stuff' => 'hide-me'],
            'ip_address' => '192.168.1.20',
        ]);

        $page = $this->actingAs($admin)->get(route('dhp.admin.audit-logs.index'))->assertOk();
        $page->assertSee('192.168.*.*');
        $page->assertDontSee('192.168.1.20');
        $page->assertDontSee('hide-me');
        $page->assertSee('Additional protected metadata recorded.');

        $this->actingAs($admin)->get(route('dhp.admin.audit-logs.index', ['action' => 'credential_verified']))
            ->assertOk()->assertSee('<td class="mono">credential_verified</td>', false);
        $this->actingAs($admin)->get(route('dhp.admin.audit-logs.index', ['action' => 'no-such-action']))
            ->assertOk()->assertDontSee('<td class="mono">credential_verified</td>', false);

        $this->actingAs($admin)->get(route('dhp.admin.audit-logs.index', [
            'date_from' => now()->subDays(100)->toDateString(), 'date_to' => now()->toDateString(),
        ]))->assertRedirect()->assertSessionHas('error');
    }

    public function test_expiry_command_is_idempotent_and_safe(): void
    {
        $due = Credential::factory()->labTest()->expired()->create();
        $revoked = Credential::factory()->revoked()->create(['expiry_date' => now()->subDay()->toDateString()]);
        $replaced = Credential::factory()->create(['status' => 'superseded', 'expiry_date' => now()->subDay()->toDateString()]);
        $fresh = Credential::factory()->labTest()->create();

        $this->artisan('credentials:mark-expired')->assertSuccessful();

        $this->assertSame(CredentialStatus::Expired, $due->fresh()->status);
        $this->assertSame(CredentialStatus::Revoked, $revoked->fresh()->status);
        $this->assertSame(CredentialStatus::Superseded, $replaced->fresh()->status);
        $this->assertSame(CredentialStatus::Active, $fresh->fresh()->status);

        $expired = AuditLog::query()->where('action', 'credential_expired')->firstOrFail();
        $this->assertSame(['credential_type' => 'lab_test', 'has_expiry' => true], $expired->details);
        $completed = AuditLog::query()->where('action', 'credentials_expiry_job_completed')->firstOrFail();
        $this->assertSame(1, $completed->details['expired_count']);
        $this->assertNull($completed->user_id);

        $this->artisan('credentials:mark-expired')->assertSuccessful();
        $this->assertSame(1, AuditLog::query()->where('action', 'credential_expired')->count());
        $completedAgain = AuditLog::query()->where('action', 'credentials_expiry_job_completed')->latest('id')->firstOrFail();
        $this->assertSame(0, $completedAgain->details['expired_count']);
    }

    public function test_expiry_command_is_scheduled(): void
    {
        $found = false;
        foreach (app(\Illuminate\Console\Scheduling\Schedule::class)->events() as $event) {
            if (str_contains((string) ($event->command ?? ''), 'credentials:mark-expired')) {
                $found = true;
            }
        }

        $this->assertTrue($found, 'credentials:mark-expired is not scheduled.');
    }
}
