<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vital;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageQueueTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_save_triage_auto_prioritizes_abnormal_vitals(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->post('/triage/save', [
            'patient_id' => $patient->id,
            'temperature' => 40.5,
            'systolic_bp' => 180,
            'heart_rate' => 130,
            'respiratory_rate' => 30,
            'oxygen_saturation' => 85,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('encounters', [
            'patient_id' => $patient->id,
            'status' => 'triaged',
        ]);
        $this->assertDatabaseHas('vitals', [
            'patient_id' => $patient->id,
            'temperature' => 40.5,
            'priority_level' => 'Emergency',
        ]);
    }

    public function test_save_triage_keeps_manual_priority_level(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->post('/triage/save', [
            'patient_id' => $patient->id,
            'oxygen_saturation' => 85,
            'priority_level' => 'Low',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vitals', [
            'patient_id' => $patient->id,
            'priority_level' => 'Low',
        ]);
    }

    public function test_save_triage_marks_normal_vitals_as_low(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->post('/triage/save', [
            'patient_id' => $patient->id,
            'temperature' => 37.0,
            'systolic_bp' => 110,
            'heart_rate' => 72,
            'respiratory_rate' => 16,
            'oxygen_saturation' => 98,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('vitals', [
            'patient_id' => $patient->id,
            'priority_level' => 'Low',
        ]);
    }

    public function test_dashboard_orders_queue_by_priority(): void
    {
        $user = $this->adminUser();

        $emergencyPatient = Patient::factory()->create(['first_name' => 'Emergency', 'last_name' => 'Patient']);
        $lowPatient = Patient::factory()->create(['first_name' => 'Low', 'last_name' => 'Patient']);

        $emergencyEncounter = Encounter::factory()->create([
            'patient_id' => $emergencyPatient->id,
            'status' => 'triaged',
            'encounter_date' => now()->subMinutes(30),
        ]);
        Vital::factory()->create([
            'encounter_id' => $emergencyEncounter->id,
            'patient_id' => $emergencyPatient->id,
            'priority_level' => 'Emergency',
            'temperature' => 40.5,
        ]);

        $lowEncounter = Encounter::factory()->create([
            'patient_id' => $lowPatient->id,
            'status' => 'triaged',
            'encounter_date' => now()->subMinutes(10),
        ]);
        Vital::factory()->create([
            'encounter_id' => $lowEncounter->id,
            'patient_id' => $lowPatient->id,
            'priority_level' => 'Low',
            'temperature' => 37.0,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Emergency Patient', 'Low Patient']);
    }

    public function test_consultation_form_marks_encounter_as_consultation(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'triaged',
        ]);

        $response = $this->actingAs($user)->get("/consultation/{$patient->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('encounters', [
            'id' => $encounter->id,
            'status' => 'consultation',
        ]);
    }
}