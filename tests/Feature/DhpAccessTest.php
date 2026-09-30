<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\Credential;
use App\Models\User;
use App\Services\DhpAuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Phase 2: DHP role middleware, policies, audit logger and rate limits.
 * Uses users.role only; legacy Spatie roles are left untouched.
 */
class DhpAccessTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    private function dhpUser(string $role, bool $active = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $active]);
    }

    public function test_active_citizen_reaches_own_dashboard_only(): void
    {
        $user = $this->dhpUser('citizen');

        $this->actingAs($user)->get('/citizen/dashboard')->assertOk()->assertSee('My Passport');
        $this->actingAs($user)->get('/issuer/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/verifier/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_active_issuer_reaches_issuer_dashboard_only(): void
    {
        $user = $this->dhpUser('issuer');

        $this->actingAs($user)->get('/issuer/dashboard')->assertOk()->assertSee('Issuer Dashboard');
        $this->actingAs($user)->get('/citizen/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/verifier/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_active_verifier_and_admin_reach_their_dashboards(): void
    {
        $this->actingAs($this->dhpUser('verifier'))->get('/verifier/dashboard')->assertOk()->assertSee('Verify Certificate');
        $this->actingAs($this->dhpUser('verifier'))->get('/citizen/dashboard')->assertForbidden();
        $this->actingAs($this->dhpUser('admin'))->get('/admin/dashboard')->assertOk()->assertSee('Passport Administration');
        $this->actingAs($this->dhpUser('admin'))->get('/citizen/dashboard')->assertForbidden();
    }

    public function test_inactive_users_are_logged_out_of_every_dashboard(): void
    {
        foreach (['citizen', 'issuer', 'verifier', 'admin'] as $role) {
            $user = $this->dhpUser($role, false);
            $path = $role === 'admin' ? '/admin/dashboard' : "/{$role}/dashboard";

            $this->actingAs($user)->get($path)->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/citizen/dashboard', '/issuer/dashboard', '/verifier/dashboard', '/admin/dashboard'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_citizen_policy_rejects_other_citizens_credentials(): void
    {
        $owner = $this->dhpUser('citizen');
        $stranger = $this->dhpUser('citizen');
        $ownCitizen = Citizen::factory()->create(['user_id' => $owner->id]);
        $otherCitizen = Citizen::factory()->create(['user_id' => $stranger->id]);
        $ownCredential = Credential::factory()->create(['citizen_id' => $ownCitizen->id]);
        $otherCredential = Credential::factory()->create(['citizen_id' => $otherCitizen->id]);

        $this->assertTrue($owner->can('view', $ownCredential));
        $this->assertFalse($owner->can('view', $otherCredential));
        $this->assertFalse($owner->can('view', $otherCitizen));

        $issuer = $this->dhpUser('issuer');
        $this->assertTrue($issuer->can('view', $otherCredential));
        $this->assertTrue($issuer->can('create', Credential::class));

        $verifier = $this->dhpUser('verifier');
        $this->assertFalse($verifier->can('view', $ownCredential));
        $this->assertFalse($verifier->can('view', $ownCitizen));

        $this->assertFalse($stranger->can('create', Credential::class));
    }

    public function test_one_citizen_user_links_to_only_one_citizen(): void
    {
        $user = $this->dhpUser('citizen');
        Citizen::factory()->create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);
        Citizen::factory()->create(['user_id' => $user->id]);
    }

    public function test_audit_logger_strips_sensitive_values(): void
    {
        $user = $this->dhpUser('issuer');

        DhpAuditLogger::log(
            user: $user,
            action: 'credential_issued',
            entityType: 'credential',
            entityId: 42,
            details: [
                'route' => 'dhp.issuer.dashboard',
                'national_id' => 'ABC123',
                'password' => 'secret',
                'qr_token' => str_repeat('x', 64),
                'nested' => ['result' => 'Positive', 'kept' => 'yes'],
            ],
            ipAddress: '127.0.0.1',
        );

        $row = AuditLog::query()->where('action', 'credential_issued')->latest('id')->firstOrFail();
        $this->assertSame('credential', $row->entity_type);
        $this->assertSame(42, (int) $row->entity_id);
        $this->assertSame('dhp.issuer.dashboard', $row->details['route']);
        $this->assertSame('yes', $row->details['nested']['kept']);
        $this->assertArrayNotHasKey('national_id', $row->details);
        $this->assertArrayNotHasKey('password', $row->details);
        $this->assertArrayNotHasKey('qr_token', $row->details);
        $this->assertArrayNotHasKey('result', $row->details['nested']);
    }

    public function test_pin_limiter_allows_five_then_blocks_for_ten_minutes(): void
    {
        $closure = RateLimiter::limiter('dhp-pin');
        $this->assertNotNull($closure);

        $request = Request::create('/issuer/pin', 'POST', ['passport_id' => 'MW-DHP-2026-000001'], [], [], ['REMOTE_ADDR' => '10.0.0.9']);
        $limit = $closure($request);

        $this->assertSame(5, $limit->maxAttempts);
        $this->assertSame(600, $limit->decaySeconds);
        $this->assertStringContainsString('MW-DHP-2026-000001', (string) $limit->key);
        $this->assertStringContainsString('10.0.0.9', (string) $limit->key);
    }

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        $user = $this->dhpUser('citizen');

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

            if ($attempt <= 5) {
                $response->assertRedirect();
                $this->assertNotSame(429, $response->getStatusCode());
            } else {
                $response->assertStatus(429);
            }
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'failed_login']);
    }
}
