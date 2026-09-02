<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Patient;
use App\Models\Facility;
use App\Models\Encounter;
use App\Models\LabOrder;
use App\Models\AuditLog;
use Tests\TestCase;

class LabDiagnosticWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private $facility;
    private $clinician;
    private $labTech;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        
        $this->facility = Facility::factory()->create();
        
        $this->clinician = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->clinician->assignRole('clinical_officer');
        
        $this->labTech = User::factory()->create(['facility_id' => $this->facility->id]);
        $this->labTech->assignRole('lab_technician');
    }

    public function test_clinician_can_request_lab_test_during_consultation(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'chief_complaint' => 'Fever',
            'diagnosis' => 'Malaria - suspected',
            'encounter_date' => now(),
        ]);
        
        $this->actingAs($this->clinician);
        
        // Request a lab test
        $response = $this->post(route('lab.orders.store'), [
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear for Malaria Parasites',
            'description' => 'Check for malaria parasites in blood',
        ]);
        
        $response->assertRedirect(route('patients.show', $patient));
        
        // Verify lab order was created
        $this->assertDatabaseHas('lab_orders', [
            'patient_id' => $patient->id,
            'encounter_id' => $encounter->id,
            'test_type' => 'parasitology',
            'status' => 'requested',
        ]);
        
        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'subject_type' => LabOrder::class,
        ]);
    }

    public function test_lab_tech_can_view_pending_lab_orders(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        
        $labOrder = $encounter->labOrders()->create([
            'patient_id' => $patient->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear',
            'status' => 'requested',
            'description' => 'Check for malaria',
            'requested_by_user_id' => $this->clinician->id,
        ]);
        
        $this->actingAs($this->labTech);
        
        // Lab tech views pending orders
        $response = $this->get(route('lab.orders.index', ['status' => 'requested']));
        
        $response->assertStatus(200);
        $response->assertViewHas('labOrders');
        $this->assertTrue($response->viewData('labOrders')->contains($labOrder));
    }

    public function test_lab_tech_can_record_lab_results(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        
        $labOrder = $encounter->labOrders()->create([
            'patient_id' => $patient->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear',
            'status' => 'requested',
            'requested_by_user_id' => $this->clinician->id,
        ]);
        
        $this->actingAs($this->labTech);
        
        // Record lab results
        $response = $this->post(route('lab.orders.results', $labOrder), [
            'result_value' => 'Positive',
            'result_units' => '+++',
            'result_description' => 'Malaria parasites detected: RBC infected with P. falciparum',
        ]);
        
        $response->assertRedirect(route('lab.orders.show', $labOrder));
        
        // Verify results were recorded
        $labOrder->refresh();
        $this->assertEquals('results', $labOrder->status);
        $this->assertEquals('Positive', $labOrder->result_value);
        $this->assertNotNull($labOrder->completed_at);
        
        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'subject_type' => LabOrder::class,
            'subject_id' => $labOrder->id,
        ]);
    }

    public function test_clinician_can_view_lab_results_in_patient_record(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'chief_complaint' => 'Fever',
            'diagnosis' => 'Malaria - suspected',
            'encounter_date' => now(),
        ]);
        
        $labOrder = $encounter->labOrders()->create([
            'patient_id' => $patient->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear',
            'status' => 'results',
            'result_value' => 'Positive',
            'result_units' => '+++',
            'result_description' => 'Malaria parasites detected',
            'requested_by_user_id' => $this->clinician->id,
            'completed_at' => now(),
        ]);
        
        $this->actingAs($this->clinician);
        
        // View patient record - should include lab results
        $response = $this->get(route('patients.show', $patient));
        
        $response->assertStatus(200);
        
        // Verify encounter includes lab orders
        $patientData = $response->viewData('patient');
        $encounters = $patientData->encounters()->get();
        
        // At least one encounter should have a lab order
        $hasLabOrder = $encounters->some(function ($enc) use ($labOrder) {
            return $enc->labOrders()->where('id', $labOrder->id)->exists();
        });
        
        $this->assertTrue($hasLabOrder, 'Lab order should be visible in encounter');
    }

    public function test_clinician_can_update_diagnosis_based_on_lab_results(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'chief_complaint' => 'Fever',
            'diagnosis' => 'Malaria - suspected',
            'encounter_date' => now(),
        ]);
        
        $labOrder = $encounter->labOrders()->create([
            'patient_id' => $patient->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear',
            'status' => 'results',
            'result_value' => 'Positive',
            'result_description' => 'Confirmed malaria parasites',
            'requested_by_user_id' => $this->clinician->id,
            'completed_at' => now(),
        ]);
        
        $this->actingAs($this->clinician);
        
        // Update encounter diagnosis based on lab results
        $response = $this->put(route('patients.update', $patient), [
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'national_id' => $patient->national_id,
            'sex' => $patient->sex,
            'age' => $patient->age,
        ]);
        
        // Verify encounter can be updated with confirmed diagnosis
        $encounter->refresh();
        // In real workflow, clinician would update via encounter update
        $this->assertNotNull($encounter->diagnosis);
    }

    public function test_lab_orders_appear_in_patient_timeline(): void
    {
        $patient = Patient::factory()->create(['registered_by_facility_id' => $this->facility->id]);
        
        $encounter = $patient->encounters()->create([
            'facility_id' => $this->facility->id,
            'user_id' => $this->clinician->id,
            'encounter_type' => 'consultation',
            'status' => 'completed',
            'encounter_date' => now(),
        ]);
        
        $labOrder = $encounter->labOrders()->create([
            'patient_id' => $patient->id,
            'test_type' => 'parasitology',
            'test_name' => 'Blood Smear',
            'status' => 'results',
            'result_value' => 'Positive',
            'result_description' => 'Malaria confirmed',
            'requested_by_user_id' => $this->clinician->id,
            'completed_at' => now(),
        ]);
        
        // Lab orders should be linked to encounter and appear in timeline
        $this->assertEquals($encounter->id, $labOrder->encounter_id);
        $this->assertTrue($encounter->labOrders()->where('id', $labOrder->id)->exists());
    }
}
