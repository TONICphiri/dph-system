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
            ->assertSee('Inventory')
            ->assertSee('Medical Travel Clearance');
    }

    public function test_reports_denied_without_permission(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('registration_clerk');

        $this->actingAs($user)->get('/reports')->assertForbidden();
        $this->actingAs($user)->get('/reports/census')->assertForbidden();
        $this->actingAs($user)->get('/reports/medical-clearance')->assertForbidden();
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

    public function test_medical_clearance_finds_patient_by_national_id(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Traveller',
            'national_id' => 'MW123456',
        ]);

        $this->actingAs($user)->get('/reports/medical-clearance?identifier=MW123456')
            ->assertStatus(200)
            ->assertSee($patient->full_name)
            ->assertSee('Generate Medical PDF');
    }

    public function test_medical_clearance_pdf_requires_valid_patient_identifier(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->post('/reports/medical-clearance/pdf', $this->medicalClearancePayload([
            'identifier' => 'missing-id',
        ]))
            ->assertRedirect('/reports/medical-clearance?identifier=missing-id')
            ->assertSessionHasErrors('identifier');
    }

    public function test_medical_clearance_pdf_generates_for_patient(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create([
            'dhp_id' => 'DHP-2026-00000999',
            'national_id' => 'MW999999',
        ]);

        Encounter::factory()->create([
            'patient_id' => $patient->id,
            'diagnosis' => 'Stable asthma',
            'treatment_plan' => 'Continue inhaled treatment',
        ]);

        $response = $this->actingAs($user)->post('/reports/medical-clearance/pdf', $this->medicalClearancePayload([
            'identifier' => $patient->dhp_id,
        ]));

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
    }

    private function medicalClearancePayload(array $overrides = []): array
    {
        return array_merge([
            'identifier' => 'DHP-2026-00000999',
            'diagnosis' => 'Stable asthma',
            'current_condition' => 'Patient is clinically stable.',
            'treatment_plan' => 'Continue current medication and follow up after travel.',
            'medications' => 'Salbutamol inhaler - 2 puffs as needed',
            'medical_equipment' => 'Inhaler spacer',
            'travel_clearance' => 'Patient is fit for travel with listed accommodations.',
            'flight_accommodations' => 'Carry inhaler in hand luggage.',
            'physician_name' => 'Dr. Test User',
            'physician_contact' => 'doctor@example.test',
            'issue_date' => now()->format('Y-m-d'),
        ], $overrides);
    }
}
