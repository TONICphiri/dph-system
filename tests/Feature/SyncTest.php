<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SyncQueue;
use App\Models\User;
use App\Models\Vital;
use App\Services\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_patient_registration_is_enqueued_for_sync(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->post('/patients', [
            'first_name' => 'Grace',
            'last_name' => 'Banda',
            'date_of_birth' => '1990-05-15',
            'gender' => 'F',
            'phone_number' => '0999123456',
            'district' => 'Blantyre',
        ])->assertRedirect();

        $this->assertDatabaseHas('sync_queue', [
            'record_type' => 'patients',
            'action' => 'create',
            'status' => 'pending',
        ]);

        $queue = SyncQueue::where('record_type', 'patients')->first();
        $this->assertNotNull($queue);
        $this->assertEquals('Grace', $queue->payload['first_name']);
    }

    public function test_triage_records_vitals_and_encounter_sync(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->post('/triage/save', [
            'patient_id' => $patient->id,
            'temperature' => 39.5,
            'systolic_bp' => 140,
            'diastolic_bp' => 90,
            'heart_rate' => 100,
            'respiratory_rate' => 22,
            'weight' => 60,
            'oxygen_saturation' => 95,
        ])->assertRedirect();

        $this->assertDatabaseHas('sync_queue', ['record_type' => 'encounters', 'action' => 'update']);
        $this->assertDatabaseHas('sync_queue', ['record_type' => 'vitals', 'action' => 'create']);
    }

    public function test_consultation_enqueues_prescriptions_for_sync(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'triaged']);

        $this->actingAs($user)->post('/consultation/save', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Fever and headache',
            'diagnosis' => 'Malaria',
            'treatment_plan' => 'Coartem 2x daily',
            'prescriptions' => [
                ['medication_name' => 'Paracetamol', 'dose' => '500mg', 'frequency' => '3x daily', 'quantity' => 10],
                ['medication_name' => 'Coartem', 'dose' => '20/120', 'frequency' => '2x daily', 'quantity' => 6],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sync_queue', ['record_type' => 'encounters', 'action' => 'update']);
        $this->assertDatabaseCount('sync_queue', 3);
        $this->assertSame(2, SyncQueue::where('record_type', 'prescriptions')->count());
    }

    public function test_admission_is_enqueued_for_sync(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->post('/admission/create', [
            'patient_id' => $patient->id,
            'bed_number' => 'B-202',
            'ward' => 'General',
            'admission_type' => 'emergency',
        ])->assertRedirect();

        $this->assertDatabaseHas('sync_queue', ['record_type' => 'admissions', 'action' => 'create']);
        $this->assertDatabaseHas('sync_queue', ['record_type' => 'encounters', 'action' => 'create']);
    }

    public function test_process_pending_marks_records_as_synced(): void
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        SyncQueue::factory()->count(3)->create();

        $results = SyncService::processPending(50);

        $this->assertSame(3, $results['synced']);
        $this->assertSame(0, $results['failed']);
        $this->assertDatabaseHas('sync_queue', ['status' => 'synced']);
        $this->assertDatabaseMissing('sync_queue', ['status' => 'pending']);
    }

    public function test_failed_sync_can_be_retried(): void
    {
        $user = $this->adminUser();
        $queue = SyncQueue::factory()->failed()->create();

        $response = $this->actingAs($user)->post("/sync/retry/{$queue->id}");

        $response->assertRedirect();
        // With the sync queue driver the job runs immediately, so the
        // retried record is re-processed and marked as synced
        $this->assertDatabaseHas('sync_queue', [
            'id' => $queue->id,
            'status' => 'synced',
        ]);
    }

    public function test_sync_status_page_shows_counts(): void
    {
        $user = $this->adminUser();
        SyncQueue::factory()->count(2)->create();
        SyncQueue::factory()->synced()->count(3)->create();
        SyncQueue::factory()->failed()->create();

        $response = $this->actingAs($user)->get('/sync/status');

        $response->assertStatus(200)
            ->assertSee('Sync Status')
            ->assertSee('Pending')
            ->assertSee('Failed');
    }
}