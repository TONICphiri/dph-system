<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_reports_index_requires_view_reports(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->get('/reports')
            ->assertStatus(200)
            ->assertSee('Patient Census')
            ->assertSee('OPD Visits')
            ->assertSee('Dispensed Medications')
            ->assertSee('Inventory');
    }

    public function test_reports_denied_without_permission(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('registration_clerk');

        $this->actingAs($user)->get('/reports')->assertForbidden();
        $this->actingAs($user)->get('/reports/census')->assertForbidden();
    }

    public function test_census_report_shows_totals(): void
    {
        $user = $this->adminUser();
        Patient::factory()->count(5)->create(['status' => 'active']);
        Patient::factory()->count(2)->create(['status' => 'inactive']);

        $this->actingAs($user)->get('/reports/census')
            ->assertStatus(200)
            ->assertSee('Total Registered')
            ->assertSee('By Gender')
            ->assertSee('Top Districts')
            ->assertSee('Recently Registered');
    }

    public function test_opd_visits_report_shows_encounters(): void
    {
        $user = $this->adminUser();
        $patients = Patient::factory()->count(3)->create();
        foreach ($patients as $patient) {
            Encounter::factory()->create([
                'patient_id' => $patient->id,
                'encounter_type' => 'OPD',
                'status' => 'completed',
            ]);
        }

        $this->actingAs($user)->get('/reports/opd-visits')
            ->assertStatus(200)
            ->assertSee('Total Visits')
            ->assertSee('By Visit Type')
            ->assertSee('Visits Per Day');
    }

    public function test_admissions_report_shows_totals(): void
    {
        $user = $this->adminUser();
        $patients = Patient::factory()->count(2)->create();
        foreach ($patients as $patient) {
            Admission::factory()->create([
                'patient_id' => $patient->id,
                'status' => 'active',
                'ward_name' => 'General',
            ]);
        }

        $this->actingAs($user)->get('/reports/admissions')
            ->assertStatus(200)
            ->assertSee('Total Admissions')
            ->assertSee('By Ward')
            ->assertSee('By Admission Type');
    }

    public function test_dispensed_meds_report_shows_dispensed(): void
    {
        $user = $this->adminUser();
        $patients = Patient::factory()->count(2)->create();
        foreach ($patients as $patient) {
            Prescription::factory()->create([
                'patient_id' => $patient->id,
                'status' => 'dispensed',
                'dispensed_at' => now(),
            ]);
        }

        $this->actingAs($user)->get('/reports/dispensed-meds')
            ->assertStatus(200)
            ->assertSee('Prescriptions Dispensed')
            ->assertSee('By Medication');
    }

    public function test_inventory_report_shows_stock_levels(): void
    {
        $user = $this->adminUser();
        Inventory::factory()->count(3)->create();
        Inventory::factory()->lowStock()->create();

        $this->actingAs($user)->get('/reports/inventory')
            ->assertStatus(200)
            ->assertSee('Low Stock')
            ->assertSee('Out of Stock')
            ->assertSee('Expired');
    }
}