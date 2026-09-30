<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Phase 9: branding, role navigation, status language, accessibility,
 * print privacy and hospital-scope separation across DHP views.
 */
class DhpUiTest extends TestCase
{
    use RefreshDatabase;

    // Seed like the legacy suites so every execution order finds demo data.
    protected bool $seed = true;

    public function test_each_role_sees_only_its_navigation(): void
    {
        $citizen = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        Citizen::factory()->create(['user_id' => $citizen->id]);

        $this->actingAs($citizen)->get(route('dhp.citizen.dashboard'))->assertOk()
            ->assertSee('Digital Health Passport')
            ->assertSee('My Passport')
            ->assertSee('Logout')
            ->assertDontSee('Search Citizen')
            ->assertDontSee('Issue Credential')
            ->assertDontSee('Verify Certificate')
            ->assertDontSee('Users')
            ->assertDontSee('Facilities')
            ->assertDontSee('Audit Log')
            ->assertDontSee('Backups');

        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);
        $this->actingAs($issuer)->get(route('dhp.issuer.dashboard'))->assertOk()
            ->assertSee('Issuer Dashboard')
            ->assertSee('Search Citizen')
            ->assertSee('Register Citizen')
            ->assertDontSee('My Passport')
            ->assertDontSee('Audit Log')
            ->assertDontSee('Backups');

        $verifier = User::factory()->create(['role' => 'verifier', 'is_active' => true]);
        $this->actingAs($verifier)->get(route('dhp.verifier.dashboard'))->assertOk()
            ->assertSee('Verify Certificate')
            ->assertDontSee('Search Citizen')
            ->assertDontSee('Issue Credential')
            ->assertDontSee('My Passport')
            ->assertDontSee('Users');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('dhp.admin.dashboard'))->assertOk()
            ->assertSee('Administration')
            ->assertSee('Users')
            ->assertSee('Facilities')
            ->assertSee('Audit Log')
            ->assertSee('Backups')
            ->assertDontSee('Search Citizen')
            ->assertDontSee('Issue Credential')
            ->assertDontSee('My Passport');
    }

    public function test_status_badge_maps_every_state(): void
    {
        $expected = [
            'active' => ['Active', 'badge-success'],
            'expired' => ['Expired', 'badge-warning'],
            'revoked' => ['Revoked', 'badge-danger'],
            'superseded' => ['Replaced', 'badge-neutral'],
            'valid' => ['Valid', 'badge-success'],
            'invalid' => ['Invalid', 'badge-danger'],
            'not_found' => ['Not Found', 'badge-danger'],
        ];

        foreach ($expected as $status => [$text, $class]) {
            $html = Blade::render('<x-dhp.status-badge :status="$s" />', ['s' => $status]);
            $this->assertStringContainsString($text, $html, $status);
            $this->assertStringContainsString($class, $html, $status);
        }
    }

    public function test_effective_expiry_displays_expired_without_command(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $citizen = Citizen::factory()->create(['user_id' => $user->id]);
        Credential::factory()->expired()->create(['citizen_id' => $citizen->id]);

        $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk()->assertSee('Expired');
    }

    public function test_forms_have_labels_helpers_and_no_sensitive_repopulation(): void
    {
        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);

        $form = $this->actingAs($issuer)->get(route('dhp.issuer.citizens.create'))->assertOk();
        $form->assertSee('<label', false);
        $form->assertSee('It is not a password.');
        $form->assertDontSee('type="password"', false);

        $citizen = Citizen::factory()->create(['district' => 'Blantyre']);
        $this->actingAs($issuer)->post(route('dhp.issuer.citizens.confirm-identity'), [
            'citizen_id' => $citizen->id,
            'first_name' => $citizen->first_name,
            'last_name' => $citizen->last_name,
        ]);
        $issue = $this->actingAs($issuer)->get(route('dhp.issuer.credentials.create', $citizen))->assertOk();
        $issue->assertSee('It is not shown during public verification.');
    }

    public function test_empty_states_use_safe_messages(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        Citizen::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk()
            ->assertSee('No health credentials are available in your passport yet.');

        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);
        $this->actingAs($issuer)->get(route('dhp.issuer.citizens.search', ['query' => 'Nobody matches this']))
            ->assertOk()->assertSee('No matching citizen was found.');

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('dhp.admin.audit-logs.index', ['action' => 'no-such-action']))
            ->assertOk()->assertSee('No audit activity matched your filters.');
        $this->actingAs($admin)->get(route('dhp.admin.backups.index'))->assertOk()
            ->assertSee('No backup activity has been recorded yet.');
    }

    public function test_accessibility_attributes_present(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $citizen = Citizen::factory()->create(['user_id' => $user->id]);
        Credential::factory()->create(['citizen_id' => $citizen->id]);

        $page = $this->actingAs($user)->get(route('dhp.citizen.dashboard'))->assertOk();
        $page->assertSee('aria-expanded="false"', false);
        $page->assertSee('aria-controls="qr-', false);
        $page->assertSee('aria-label="QR code for credential verification"', false);
        $page->assertSee('<h1', false);

        $issuer = User::factory()->create(['role' => 'issuer', 'is_active' => true]);
        $this->actingAs($issuer)->get(route('dhp.issuer.dashboard'))->assertOk()
            ->assertSee('aria-controls="dhp-menu"', false)
            ->assertSee('aria-label="Open passport menu"', false);
    }

    public function test_print_views_hide_chrome_and_keep_exclusions(): void
    {
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);
        $citizen = Citizen::factory()->create(['user_id' => $user->id, 'national_id' => 'UIPRINT1']);
        $credential = Credential::factory()->create(['citizen_id' => $citizen->id]);

        $print = $this->actingAs($user)->get(route('dhp.citizen.credentials.print', $credential))->assertOk();
        $print->assertSee('no-print', false);
        $print->assertSee('window.print', false);
        $print->assertDontSee('UIPRINT1');
    }

    public function test_dhp_scope_has_no_hospital_terminology(): void
    {
        $pattern = '/\b(patients?|wards?|admissions?|appointments?|billing|prescriptions?|pharmacy|diagnos(is|es)|triage|theatre|insurance|beds?|clinical notes?|doctor schedules?|nurse schedules?|medical history|hospital system|patient record|patient management)\b/i';

        $roots = [
            resource_path('views/dhp'),
            resource_path('views/mail/dhp'),
            resource_path('views/components/dhp'),
            app_path('Http/Controllers/Dhp'),
            app_path('Notifications/Dhp'),
        ];

        $paths = [];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (str_ends_with($file->getFilename(), '.php')) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->assertDoesNotMatchRegularExpression(
                $pattern,
                file_get_contents($path),
                'Hospital terminology in DHP scope: '.$path
            );
        }
    }
}
