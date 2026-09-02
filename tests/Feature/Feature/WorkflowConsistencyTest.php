<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutPrompts;

class WorkflowConsistencyTest extends TestCase
{
    use RefreshDatabaseWithoutPrompts;

    private $facility;
    private $clinician;
    private $doctor;
    private $pharmacist;
    private $triageNurse;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        
        $this->facility = Facility::factory()->create();
        
        $this->triageNurse = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->triageNurse->assignRole('triage_nurse');
        
        $this->clinician = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->clinician->assignRole('clinical_officer');
        
        $this->doctor = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->doctor->assignRole('doctor');
        
        $this->pharmacist = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->pharmacist->assignRole('pharmacist');
    }

    public function test_encounter_status_transitions_follow_workflow(): void
    {
        // Register a patient
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        // Create initial encounter with 'registered' status
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->triageNurse->id,
            'encounter_type' => 'triage',
            'status' => 'registered',
            'encounter_date' => now(),
        ]);
        
        $this->assertEquals('registered', $encounter->status);
        
        // Perform triage - should transition to 'triaged'
        $this->actingAs($this->triageNurse);
        $response = $this->post(route('triage.save'), [
            'patient_id' => $patient->id,
            'weight' => 70,
            'temperature' => 37.5,
            'systolic_bp' => 120,
            'diastolic_bp' => 80,
            'heart_rate' => 80,
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
            'priority_level' => 'Medium',
        ]);
        
        $encounter->refresh();
        $this->assertEquals('triaged', $encounter->status, 'Encounter should be triaged after triage vitals recorded');
        
        // Enter consultation - should transition to 'consultation'
        $this->actingAs($this->clinician);
        $this->get(route('consultation', $patient));
        
        $encounter->refresh();
        $this->assertEquals('consultation', $encounter->status, 'Encounter should be in consultation when consultation form is opened');
        
        // Save consultation - should transition to 'completed'
        $response = $this->post(route('consultation.save'), [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Fever',
            'examination_findings' => 'Temperature elevated',
            'diagnosis' => 'Malaria',
            'treatment_plan' => 'Artemether',
            'requires_admission' => false,
            'prescriptions' => [
                [
                    'medication_name' => 'Artemether',
                    'dose' => '80mg',
                    'frequency' => 'Once daily',
                    'quantity' => 7,
                    'duration' => '7 days',
                ],
            ],
        ]);
        
        $encounter->refresh();
        $this->assertEquals('completed', $encounter->status, 'Encounter should be completed after consultation saved');
        
        // Verify prescriptions are in 'pending' status
        $this->assertEquals(1, $encounter->prescriptions()->where('status', 'pending')->count());
    }

    public function test_only_pending_prescriptions_can_be_dispensed(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        
        $prescription = $encounter->prescriptions()->create([
            'patient_id' => $patient->id,
            'prescribed_by_user_id' => $this->clinician->id,
            'medication_name' => 'Aspirin',
            'dose' => '500mg',
            'frequency' => 'Twice daily',
            'quantity' => 14,
            'status' => 'pending',
            'prescribed_at' => now(),
        ]);
        
        $this->actingAs($this->pharmacist);
        
        // Dispense the pending prescription - should succeed
        $response = $this->post(route('pharmacy.dispense'), [
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'quantity_dispensed' => 14,
        ]);
        
        $response->assertRedirect(route('patients.show', $patient));
        
        $prescription->refresh();
        $this->assertEquals('dispensed', $prescription->status);
        
        // Try to dispense again - should fail because it's no longer pending
        $response = $this->post(route('pharmacy.dispense'), [
            'patient_id' => $patient->id,
            'prescription_id' => $prescription->id,
            'quantity_dispensed' => 14,
        ]);
        
        // Should be redirected back with error
        $response->assertRedirect();
    }

    public function test_duplicate_active_admissions_prevented(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter1 = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        
        $this->actingAs($this->clinician);
        
        // Create first admission with proper field names
        $response = $this->post(route('admission.create'), [
            'patient_id' => $patient->id,
            'bed_number' => 'B101',
            'ward' => 'General Ward',
            'admission_type' => 'emergency',
            'admission_reason' => 'High fever',
        ]);
        
        $response->assertRedirect(route('patients.show', $patient));
        
        // Verify admission was created
        $this->assertEquals(1, $patient->admissions()->where('status', 'active')->count());
        
        // Try to create another admission without discharging the first
        $response = $this->post(route('admission.create'), [
            'patient_id' => $patient->id,
            'bed_number' => 'S202',
            'ward' => 'Surgical Ward',
            'admission_type' => 'emergency',
            'admission_reason' => 'Need surgery',
        ]);
        
        $response->assertRedirect();
        // Should still have only one active admission
        $this->assertEquals(1, $patient->admissions()->where('status', 'active')->count());
    }

    public function test_discharge_closes_active_admission(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->doctor->id,
            'encounter_type' => 'admission',
            'status' => 'admitted',
            'encounter_date' => now(),
        ]);
        
        $admission = $patient->admissions()->create([
            'encounter_id' => $encounter->id,
            'facility_id' => $this->facility->id,
            'admitted_by_user_id' => $this->doctor->id,
            'ward_name' => 'General Ward',
            'admission_type' => 'emergency',
            'admission_reason' => 'Malaria',
            'admitted_at' => now()->subDays(3),
            'status' => 'active',
        ]);
        
        $this->actingAs($this->doctor);
        
        // Discharge patient (doctor has discharge_patient permission)
        $this->post(route('discharge', $patient), [
            'final_diagnosis' => 'Malaria - Treated',
            'discharge_date' => now(),
        ]);
        
        $admission->refresh();
        $this->assertEquals('discharged', $admission->status);
    }
}
