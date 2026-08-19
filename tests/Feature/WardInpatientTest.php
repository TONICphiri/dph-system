<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\MedicationAdministration;
use App\Models\Patient;
use App\Models\ProgressNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WardInpatientTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_ward_page_shows_active_admission(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        Admission::factory()->create([
            'patient_id' => $patient->id,
            'ward_name' => 'Pediatrics',
            'bed_number' => 'A-101',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get("/ward/{$patient->id}");

        $response->assertStatus(200)
            ->assertSee('Medication Administration Log')
            ->assertSee('Daily Progress Notes')
            ->assertSee('Pediatrics');
    }

    public function test_medication_can_be_administered(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $admission = Admission::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/ward/medication-admin', [
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'dose' => '500mg',
            'route' => 'PO',
            'notes' => 'After breakfast',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('medication_administrations', [
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'dose' => '500mg',
            'route' => 'PO',
        ]);
    }

    public function test_medication_administration_requires_active_admission(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->post('/ward/medication-admin', [
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'dose' => '500mg',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('medication_administrations', 0);
    }

    public function test_progress_note_can_be_saved(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $admission = Admission::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post('/ward/progress-note', [
            'patient_id' => $patient->id,
            'note' => 'Patient improving, fever resolved.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('progress_notes', [
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'note' => 'Patient improving, fever resolved.',
        ]);
    }

    public function test_progress_note_requires_active_admission(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->post('/ward/progress-note', [
            'patient_id' => $patient->id,
            'note' => 'Test note',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('progress_notes', 0);
    }

    public function test_ward_page_lists_existing_records(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $admission = Admission::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'active',
        ]);
        MedicationAdministration::factory()->create([
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Amoxicillin',
            'dose' => '250mg',
        ]);
        ProgressNote::factory()->create([
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'note' => 'Patient stable overnight.',
        ]);

        $response = $this->actingAs($user)->get("/ward/{$patient->id}");

        $response->assertStatus(200)
            ->assertSee('Amoxicillin')
            ->assertSee('Patient stable overnight.');
    }
}