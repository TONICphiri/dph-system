<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationPrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_consultation_saves_prescriptions(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'consultation',
        ]);

        $response = $this->actingAs($user)->post('/consultation/save', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Fever and cough',
            'diagnosis' => 'Upper respiratory infection',
            'prescriptions' => [
                [
                    'medication_name' => 'Paracetamol',
                    'dose' => '500mg',
                    'frequency' => '3x daily',
                    'quantity' => 30,
                    'duration' => '7 days',
                    'instructions' => 'After meals',
                ],
                [
                    'medication_name' => 'Amoxicillin',
                    'dose' => '250mg',
                    'frequency' => '3x daily',
                    'quantity' => 21,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('prescriptions', [
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'dose' => '500mg',
            'frequency' => '3x daily',
            'quantity' => 30,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('prescriptions', [
            'encounter_id' => $encounter->id,
            'medication_name' => 'Amoxicillin',
            'dose' => '250mg',
        ]);
        $this->assertDatabaseCount('prescriptions', 2);
    }

    public function test_consultation_marks_encounter_completed(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'consultation',
        ]);

        $response = $this->actingAs($user)->post('/consultation/save', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Headache',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('encounters', [
            'id' => $encounter->id,
            'status' => 'completed',
        ]);
    }

    public function test_consultation_without_prescriptions_saves_notes_only(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'consultation',
        ]);

        $response = $this->actingAs($user)->post('/consultation/save', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Routine checkup',
            'diagnosis' => 'No abnormality detected',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('encounters', [
            'id' => $encounter->id,
            'diagnosis' => 'No abnormality detected',
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('prescriptions', 0);
    }

    public function test_consultation_page_shows_prescription_section(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->get("/consultation/{$patient->id}");

        $response->assertStatus(200)
            ->assertSee('Prescriptions')
            ->assertSee('add-prescription')
            ->assertSee('inventory-medications');
    }
}