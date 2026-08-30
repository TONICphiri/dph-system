<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

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
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['email' => 'nurse@demo.mw']);
        $user = User::where('email', 'nurse@demo.mw')->first();
        $this->assertTrue($user->hasRole('triage_nurse'));
    }
}
