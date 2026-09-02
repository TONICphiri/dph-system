<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class AdminManagementTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_view_facility_management_page(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/facilities');

        $response->assertStatus(200);
        $response->assertViewIs('admin.facilities.index');
    }

    public function test_admin_can_create_a_new_facility(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/facilities', [
            'name' => 'Chipata Health Centre',
            'facility_code' => 'CHP-999',
            'facility_type' => 'Health Centre',
            'district' => 'Chipata',
            'region' => 'Eastern',
            'address' => 'Chipata Road',
            'phone_number' => '+265 999 123 456',
            'email' => 'chipata@example.com',
            'status' => 'active',
        ]);

        $response->assertRedirect('/facilities');
        $this->assertDatabaseHas('facilities', ['name' => 'Chipata Health Centre']);
    }

    public function test_admin_can_create_a_user_with_role_and_facility(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Test Nurse',
            'email' => 'nurse@demo.mw',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'facility_id' => $facility->id,
            'role' => 'triage_nurse',
            'status' => 'active',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'nurse@demo.mw', 'status' => 'active']);
        $user = User::where('email', 'nurse@demo.mw')->first();
        $this->assertTrue($user->hasRole('triage_nurse'));
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $facility = Facility::factory()->create();
        $user = User::factory()->create([
            'facility_id' => $facility->id,
            'email' => 'inactive@demo.mw',
            'status' => 'inactive',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'inactive@demo.mw',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $admin->assignRole('admin');

        $user = User::factory()->create([
            'facility_id' => $facility->id,
            'email' => 'staff@demo.mw',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('users.toggle-status', $user));

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'inactive']);

        $response = $this->actingAs($admin)->post(route('users.toggle-status', $user));

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'active']);
    }

    public function test_facility_list_shows_admin_summary_and_staff_counts(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');

        $facility = Facility::factory()->create();
        User::factory()->count(2)->create(['facility_id' => $facility->id, 'status' => 'active']);
        User::factory()->create(['facility_id' => $facility->id, 'status' => 'inactive']);

        $response = $this->actingAs($admin)->get('/facilities');

        $response->assertStatus(200);
        $response->assertSeeText('Facility Summary');
        $response->assertSeeText('4 Total Staff');
        $response->assertSeeText('3 Active Staff');
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $admin->assignRole('admin');

        \App\Models\AuditLog::create([
            'action' => 'create',
            'subject_type' => \App\Models\User::class,
            'subject_id' => $admin->id,
            'user_id' => $admin->id,
            'description' => 'System audit entry created for testing.',
        ]);

        $response = $this->actingAs($admin)->get('/audit-logs');

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit-logs.index');
        $response->assertSeeText('Audit Logs');
        $response->assertSeeText('System audit entry created for testing.');
    }
}
