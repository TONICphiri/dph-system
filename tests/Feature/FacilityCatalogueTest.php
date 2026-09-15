<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class FacilityCatalogueTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    protected function facilityAdmin(): User
    {
        $facility = Facility::factory()->create();
        $admin = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $admin->assignRole('facility_admin');

        return $admin;
    }

    public function test_facility_admin_dashboard_renders_catalogue_section(): void
    {
        $response = $this->actingAs($this->facilityAdmin())->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Enrollment & verification', false);
        $response->assertSee('Enroll user with NIN');
        $response->assertSee('Approval queue');
    }

    public function test_facility_admin_can_open_enroll_pages(): void
    {
        $admin = $this->facilityAdmin();

        $this->actingAs($admin)->get('/enroll/create')->assertStatus(200);
        $this->actingAs($admin)->get('/enroll/pending')->assertStatus(200);
        $this->actingAs($admin)->get('/facility/approvals')->assertStatus(200);
        $this->actingAs($admin)->get('/facility/identity-services')->assertStatus(200);
    }

    public function test_public_verify_scan_renders(): void
    {
        $this->get('/verify/scan')->assertStatus(200);
    }

    public function test_lab_pages_render_with_simple_styling(): void
    {
        $facility = Facility::factory()->create();
        $tech = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $tech->assignRole('lab_technician');
        $officer = User::factory()->create(['facility_id' => $facility->id, 'status' => 'active']);
        $officer->assignRole('clinical_officer');
        $patient = \App\Models\Patient::factory()->create(['registered_by_facility_id' => $facility->id]);

        // Index + patient listing (lab technician).
        $this->actingAs($tech)->get('/lab/orders')->assertStatus(200)->assertSee('Lab Orders', false);
        $this->actingAs($tech)->get("/lab/orders/patient/{$patient->id}")->assertStatus(200)->assertSee($patient->full_name);

        // Create form (clinical officer) then show page for the stored order.
        $this->actingAs($officer)->get("/lab/orders/create/{$patient->id}")->assertStatus(200)->assertSee('Create Lab Order');
        $encounter = $patient->encounters()->create([
            'facility_id' => $facility->id,
            'user_id' => $officer->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        $this->actingAs($officer)->post(route('lab.orders.store'), [
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'test_type' => 'Malaria RDT',
            'test_name' => 'Malaria Rapid Diagnostic Test',
        ])->assertRedirect(route('patients.show', $patient));

        $order = \App\Models\LabOrder::where('patient_id', $patient->id)->first();
        $this->assertNotNull($order);
        $this->actingAs($tech)->get("/lab/orders/{$order->id}")->assertStatus(200)->assertSee('Lab Order Details');
    }
}
