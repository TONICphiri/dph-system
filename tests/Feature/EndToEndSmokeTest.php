<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SyncQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EndToEndSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_all_seeded_users_can_log_in(): void
    {
        $this->seedUsers();

        $emails = [
            'admin@dhp.mw',
            'clerk@lilongwe.dhp.mw',
            'triage@lilongwe.dhp.mw',
            'doctor@lilongwe.dhp.mw',
            'pharmacy@lilongwe.dhp.mw',
            'clerk@ndirande.dhp.mw',
            'triage@ndirande.dhp.mw',
            'doctor@ndirande.dhp.mw',
            'pharmacy@ndirande.dhp.mw',
        ];

        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "Seeded user {$email} exists");

            $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ])->assertRedirect('/dashboard');

            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_full_opd_flow_works_across_roles(): void
    {
        $this->seedUsers();

        $clerk = User::where('email', 'clerk@lilongwe.dhp.mw')->first();
        $nurse = User::where('email', 'triage@lilongwe.dhp.mw')->first();
        $doctor = User::where('email', 'doctor@lilongwe.dhp.mw')->first();
        $pharmacist = User::where('email', 'pharmacy@lilongwe.dhp.mw')->first();

        // 1. Registration clerk registers a patient
        $register = $this->actingAs($clerk)->post('/patients', [
            'first_name' => 'Smoke',
            'last_name' => 'Test',
            'date_of_birth' => '1992-03-10',
            'gender' => 'F',
            'phone_number' => '0999 000 111',
            'district' => 'Lilongwe',
        ]);
        $register->assertRedirect();

        $patient = Patient::where('first_name', 'Smoke')->first();
        $this->assertNotNull($patient);
        $this->assertNotNull($patient->dhp_id);

        // 2. Patient appears in the registry
        $this->actingAs($clerk)->get('/patients')
            ->assertStatus(200)
            ->assertSee('Smoke Test');

        // 3. Triage nurse records vitals
        $this->actingAs($nurse)->post('/triage/save', [
            'patient_id' => $patient->id,
            'temperature' => 38.5,
            'systolic_bp' => 130,
            'diastolic_bp' => 85,
            'heart_rate' => 90,
            'respiratory_rate' => 20,
            'weight' => 62,
            'oxygen_saturation' => 97,
        ])->assertRedirect();

        $encounter = $patient->encounters()->latest()->first();
        $this->assertNotNull($encounter);
        $this->assertSame('triaged', $encounter->status);
        $this->assertSame(1, $encounter->vitals()->count());

        // 4. Doctor sees the patient in the consultation queue and consults
        $this->actingAs($doctor)->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Smoke Test');

        $this->actingAs($doctor)->post('/consultation/save', [
            'patient_id' => $patient->id,
            'chief_complaint' => 'Fever and cough',
            'diagnosis' => 'Malaria',
            'treatment_plan' => 'Antimalarial + paracetamol',
            'prescriptions' => [
                ['medication_name' => 'Coartem', 'dose' => '20/120', 'frequency' => '2x daily', 'quantity' => 6],
                ['medication_name' => 'Paracetamol', 'dose' => '500mg', 'frequency' => '3x daily', 'quantity' => 12],
            ],
        ])->assertRedirect();

        $this->assertSame(2, $encounter->prescriptions()->count());
        $this->assertSame('completed', $encounter->fresh()->status);

        // 5. Pharmacist sees pending prescriptions and dispenses
        $this->actingAs($pharmacist)->get("/pharmacy/{$patient->id}")
            ->assertStatus(200)
            ->assertSee('Coartem')
            ->assertSee('Paracetamol');

        $pending = $encounter->prescriptions()->where('status', 'pending')->get();
        foreach ($pending as $prescription) {
            $this->actingAs($pharmacist)->post('/pharmacy/dispense', [
                'patient_id' => $patient->id,
                'prescription_id' => $prescription->id,
            ])->assertRedirect();
        }

        $this->assertSame(0, $encounter->prescriptions()->where('status', 'pending')->count());
        $this->assertSame(2, $encounter->prescriptions()->where('status', 'dispensed')->count());

        // 6. The full flow generated sync queue entries
        $this->assertTrue(SyncQueue::where('record_type', 'patients')->count() >= 1);
        $this->assertTrue(SyncQueue::where('record_type', 'prescriptions')->count() >= 2);
    }

    public function test_full_inpatient_flow_works_across_roles(): void
    {
        $this->seedUsers();

        $clerk = User::where('email', 'clerk@lilongwe.dhp.mw')->first();
        $doctor = User::where('email', 'doctor@lilongwe.dhp.mw')->first();

        // Register patient and admit directly
        $this->actingAs($clerk)->post('/patients', [
            'first_name' => 'Inpatient',
            'last_name' => 'Smoke',
            'date_of_birth' => '1985-07-22',
            'gender' => 'M',
            'phone_number' => '0999 222 333',
            'district' => 'Blantyre',
        ])->assertRedirect();

        $patient = Patient::where('first_name', 'Inpatient')->first();
        $this->assertNotNull($patient);

        // Doctor admits the patient
        $this->actingAs($doctor)->post('/admission/create', [
            'patient_id' => $patient->id,
            'bed_number' => 'A-101',
            'ward' => 'General',
            'admission_type' => 'emergency',
        ])->assertRedirect();

        $admission = $patient->admissions()->latest()->first();
        $this->assertNotNull($admission);
        $this->assertSame('active', $admission->status);

        // Ward page shows the admitted patient
        $this->actingAs($doctor)->get("/ward/{$patient->id}")
            ->assertStatus(200)
            ->assertSee('Medication Administration Log')
            ->assertSee('General');

        // Administer medication during the stay
        $this->actingAs($doctor)->post('/ward/medication-admin', [
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'dose' => '500mg',
            'route' => 'PO',
            'notes' => 'Every 6 hours',
        ])->assertRedirect();

        $this->assertSame(1, $admission->medicationAdministrations()->count());

        // Record a progress note
        $this->actingAs($doctor)->post('/ward/progress-note', [
            'patient_id' => $patient->id,
            'note' => 'Patient stable, fever reducing',
        ])->assertRedirect();

        $this->assertSame(1, $admission->progressNotes()->count());

        // Discharge the patient
        $this->actingAs($doctor)->post("/discharge/{$patient->id}", [
            'final_diagnosis' => 'Severe malaria',
            'follow_up_instructions' => 'Complete the antimalarial course',
        ])->assertRedirect();

        $this->assertSame('discharged', $admission->fresh()->status);

        // Patient status and encounter updated
        $this->assertSame('completed', $patient->encounters()->latest()->first()->status);
    }

    public function test_admin_can_access_all_modules(): void
    {
        $this->seedUsers();

        $admin = User::where('email', 'admin@dhp.mw')->first();

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/patients')->assertStatus(200);
        $this->actingAs($admin)->get('/patients/create')->assertStatus(200);
        $this->actingAs($admin)->get('/inventory')->assertStatus(200);
        $this->actingAs($admin)->get('/inventory/create')->assertStatus(200);
        $this->actingAs($admin)->get('/reports')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/census')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/opd-visits')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/admissions')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/dispensed-meds')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/inventory')->assertStatus(200);
        $this->actingAs($admin)->get('/sync/status')->assertStatus(200);
    }

    public function test_all_parametrized_get_routes_respond(): void
    {
        $this->seedUsers();

        $admin = User::where('email', 'admin@dhp.mw')->first();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'triaged']);
        $inventory = Inventory::factory()->create();

        $routes = [
            "/patients/{$patient->id}",
            "/patients/{$patient->id}/edit",
            "/patients/{$patient->id}/qr-code",
            "/triage/{$patient->id}",
            "/consultation/{$patient->id}",
            "/pharmacy/{$patient->id}",
            "/admission/{$patient->id}",
            "/inventory/{$inventory->id}/edit",
        ];

        // Ward page needs an active admission
        Admission::factory()->create([
            'patient_id' => $patient->id,
            'status' => 'active',
            'ward_name' => 'General',
        ]);
        $routes[] = "/ward/{$patient->id}";

        foreach ($routes as $route) {
            $this->actingAs($admin)->get($route)->assertStatus(200, "Route {$route} did not respond 200");
        }
    }
}