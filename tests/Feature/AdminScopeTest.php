<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class AdminScopeTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function makeFacilityAdmin(Facility $facility): User
    {
        $user = User::factory()->create(['facility_id' => $facility->id]);
        $user->assignRole('facility_admin');

        return $user;
    }

    protected function makeNationalAdmin(?Facility $facility = null): User
    {
        $user = User::factory()->create(['facility_id' => $facility?->id]);
        $user->assignRole('national_admin');

        return $user;
    }

    public function test_facility_admin_sees_only_own_facility_users(): void
    {
        $own = Facility::factory()->create();
        $other = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);
        $teammate = User::factory()->create(['facility_id' => $own->id]);
        $outsider = User::factory()->create(['facility_id' => $other->id]);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
        $response->assertSee($teammate->name);
        $response->assertDontSee($outsider->name);
    }

    public function test_national_admin_sees_users_from_all_facilities(): void
    {
        $a = Facility::factory()->create();
        $b = Facility::factory()->create();
        $national = $this->makeNationalAdmin($a);
        $userB = User::factory()->create(['facility_id' => $b->id]);

        $response = $this->actingAs($national)->get('/users');

        $response->assertStatus(200);
        $response->assertSee($userB->name);
    }

    public function test_facility_admin_cannot_create_user_in_another_facility(): void
    {
        $own = Facility::factory()->create();
        $other = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@demo.mw',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'facility_id' => $other->id,
            'role' => 'triage_nurse',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'sneaky@demo.mw']);
    }

    public function test_facility_admin_cannot_assign_national_roles(): void
    {
        $own = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Elevated User',
            'email' => 'elevated@demo.mw',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'facility_id' => $own->id,
            'role' => 'national_admin',
            'status' => 'active',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'elevated@demo.mw']);
    }

    public function test_facility_admin_cannot_edit_user_from_another_facility(): void
    {
        $own = Facility::factory()->create();
        $other = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);
        $outsider = User::factory()->create(['facility_id' => $other->id]);

        $this->actingAs($admin)->get("/users/{$outsider->id}/edit")->assertForbidden();
        $this->actingAs($admin)->post("/users/{$outsider->id}/toggle-status")->assertForbidden();
    }

    public function test_inventory_is_scoped_to_own_facility(): void
    {
        $own = Facility::factory()->create();
        $other = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);
        $mine = Inventory::factory()->create(['facility_id' => $own->id, 'medication_name' => 'OwnMed']);
        $theirs = Inventory::factory()->create(['facility_id' => $other->id, 'medication_name' => 'OtherMed']);

        $response = $this->actingAs($admin)->get('/inventory');

        $response->assertStatus(200);
        $response->assertSee('OwnMed');
        $response->assertDontSee('OtherMed');

        $this->actingAs($admin)->post("/inventory/{$theirs->id}/restock", ['restock_quantity' => 5])
            ->assertForbidden();
        $this->actingAs($admin)->post("/inventory/{$mine->id}/restock", ['restock_quantity' => 5])
            ->assertRedirect('/inventory');
    }

    public function test_audit_logs_are_scoped_to_own_facility(): void
    {
        $own = Facility::factory()->create();
        $other = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);
        $teammate = User::factory()->create(['facility_id' => $own->id]);
        $outsider = User::factory()->create(['facility_id' => $other->id]);

        AuditLog::create(['action' => 'view', 'subject_type' => User::class, 'subject_id' => $teammate->id, 'user_id' => $teammate->id, 'description' => 'LOCAL-ENTRY-MARKER']);
        AuditLog::create(['action' => 'view', 'subject_type' => User::class, 'subject_id' => $outsider->id, 'user_id' => $outsider->id, 'description' => 'FOREIGN-ENTRY-MARKER']);

        $response = $this->actingAs($admin)->get('/audit-logs');

        $response->assertStatus(200);
        $response->assertSee('LOCAL-ENTRY-MARKER');
        $response->assertDontSee('FOREIGN-ENTRY-MARKER');
    }

    public function test_facility_admin_updates_own_profile_and_login_shows_it(): void
    {
        $own = Facility::factory()->create(['name' => 'Old Name']);
        $admin = $this->makeFacilityAdmin($own);

        $response = $this->actingAs($admin)->put('/settings/facility', [
            'name' => 'Zomba Central Hospital',
            'facility_type' => 'Hospital',
            'district' => 'Zomba',
            'address' => 'New Ward Road, Zomba',
            'phone_number' => '+265 1 555 100',
            'secondary_phone' => '+265 999 555 100',
            'email' => 'zomba@health.mw',
            'services' => "OPD care\nMaternity wing",
            'departments' => "Reception\nMaternity",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', ['id' => $own->id, 'name' => 'Zomba Central Hospital']);

        $this->post('/logout');
        $login = $this->get('/login');
        $login->assertStatus(200);
        $login->assertSee('Zomba Central Hospital');
        $login->assertSee('+265 1 555 100');
        $login->assertSee('Maternity wing');
    }

    public function test_global_settings_are_national_admin_only(): void
    {
        $own = Facility::factory()->create();
        $admin = $this->makeFacilityAdmin($own);
        $national = $this->makeNationalAdmin($own);

        $this->actingAs($admin)->get('/settings/global')->assertForbidden();

        $response = $this->actingAs($national)->put('/settings/global', [
            'display_facility_id' => $own->id,
            'passport_fields' => "Blood group\nAllergies",
            'vaccine_categories' => "BCG\nOPV",
            'health_templates' => "OPD visit\nDischarge summary",
        ]);

        $response->assertRedirect();
        $this->assertSame($own->id, (int) Setting::get('display_facility_id'));
        $this->assertContains('BCG', Setting::get('vaccine_categories'));

        $this->post('/logout');
        $this->get('/login')->assertSee($own->name);
    }

    public function test_login_redirects_each_admin_to_the_right_dashboard(): void
    {
        $own = Facility::factory()->create();
        $national = $this->makeNationalAdmin($own);
        $facilityAdmin = $this->makeFacilityAdmin($own);

        $this->post('/login', ['email' => $national->email, 'password' => 'password'])
            ->assertRedirect('/admin/dashboard');
        $this->post('/logout');

        $this->post('/login', ['email' => $facilityAdmin->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->post('/logout');
    }

    public function test_admin_dashboard_is_national_only(): void
    {
        $own = Facility::factory()->create();
        $national = $this->makeNationalAdmin($own);
        $facilityAdmin = $this->makeFacilityAdmin($own);
        $clerk = User::factory()->create(['facility_id' => $own->id]);
        $clerk->assignRole('registration_clerk');

        $this->actingAs($national)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($facilityAdmin)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($clerk)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_profile_menu_offers_manage_account_and_confirmed_logout(): void
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create(['facility_id' => $facility->id]);
        $user->assignRole('registration_clerk');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Manage account');
        $response->assertSee('profile-menu-button', false);
        $response->assertSee('logout-modal', false);
        $response->assertSee('Log out of the Health Passport?');
        $response->assertSee('Stay signed in');
    }

    public function test_national_admin_can_update_any_facility_profile(): void
    {
        $home = Facility::factory()->create();
        $other = Facility::factory()->create(['name' => 'Old Other Name']);
        $national = $this->makeNationalAdmin($home);

        // Picker lists facilities for national admins.
        $this->actingAs($national)->get('/settings/facility')
            ->assertStatus(200)
            ->assertSee('Editing facility');

        // Update the other facility through the picker target.
        $response = $this->actingAs($national)->put('/settings/facility?facility='.$other->id, [
            'name' => 'Ndirande Health Centre',
            'facility_type' => 'Health Centre',
            'district' => 'Blantyre',
            'address' => "Ndirande Township\nP.O. Box 30246, Blantyre, Malawi",
            'phone_number' => '+265 1 870 000',
            'email' => 'ndirande@health.mw',
            'working_hours' => 'Mon–Fri 07:30–17:00 · Ward 24 hrs',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('facilities', ['id' => $other->id, 'name' => 'Ndirande Health Centre']);

        // Facility admins never see the picker — own facility only.
        $facilityAdmin = $this->makeFacilityAdmin($home);
        $this->actingAs($facilityAdmin)->get('/settings/facility')
            ->assertStatus(200)
            ->assertDontSee('Editing facility');
    }

    public function test_login_without_credentials_never_opens_the_system(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();

        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_only_national_admin_can_upload_facility_logo(): void
    {
        Storage::fake('public');

        $home = Facility::factory()->create();
        $facilityAdmin = $this->makeFacilityAdmin($home);
        $national = $this->makeNationalAdmin($home);

        $profile = [
            'name' => $home->name,
            'facility_type' => $home->facility_type,
            'district' => $home->district,
            'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
        ];

        $this->actingAs($facilityAdmin)->put('/settings/facility', $profile)
            ->assertForbidden();
        $this->assertNull($home->fresh()->logo_path);

        $this->actingAs($national)->put('/settings/facility', $profile)
            ->assertRedirect();
        $this->assertNotNull($home->fresh()->logo_path);

        // The uploaded logo appears in the app header.
        $this->actingAs($national)->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('storage/'.$home->fresh()->logo_path, false);
    }
}
