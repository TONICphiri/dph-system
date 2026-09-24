<?php

namespace Tests\Feature;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\VisitStatus;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Follows a patient through registration, outpatient care and inpatient
 * care, using the same forms the staff use.
 */
class PatientWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function registerAdult(): Patient
    {
        $this->actingAs($this->user('clerk@healthpassport.mw'))
            ->post(route('patients.store'), [
                'is_child' => '0',
                'national_id' => 'AB12CD34',
                'first_name' => 'Alice',
                'last_name' => 'Mhango',
                'date_of_birth' => '1990-05-01',
                'sex' => 'female',
                'phone' => '+265 991 000 111',
                'district_id' => \App\Models\District::query()->where('name', 'Blantyre')->value('id'),
                'contacts' => [['full_name' => 'Ben Mhango', 'relationship' => 'Brother', 'phone' => '+265 888 000 222']],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        return Patient::query()->where('national_id', 'AB12CD34')->firstOrFail();
    }

    private function checkInWithVitals(Patient $patient): Visit
    {
        $this->actingAs($this->user('clerk@healthpassport.mw'))
            ->post(route('visits.store', $patient), ['reason_for_visit' => 'Fever'])
            ->assertSessionHasNoErrors();

        $visit = $patient->visits()->latest('id')->firstOrFail();
        $this->assertSame(VisitStatus::WaitingForVitals, $visit->status);

        $this->actingAs($this->user('nurse@healthpassport.mw'))
            ->post(route('vitals.store', $visit), ['temperature' => 38.2, 'weight' => 64.5, 'systolic_pressure' => 120, 'diastolic_pressure' => 80, 'pulse_rate' => 90])
            ->assertSessionHasNoErrors();

        $this->assertSame(VisitStatus::WaitingForDoctor, $visit->refresh()->status);

        return $visit;
    }

    public function test_registration_gives_a_passport_number_and_contacts(): void
    {
        $patient = $this->registerAdult();

        $this->assertMatchesRegularExpression('/^MW-\d{4}-\d{6}-\d$/', $patient->passport_number);
        $this->assertNotEmpty($patient->qr_token);
        $this->assertCount(1, $patient->emergencyContacts);
    }

    public function test_a_child_cannot_be_registered_without_the_mother(): void
    {
        $this->actingAs($this->user('clerk@healthpassport.mw'))
            ->post(route('patients.store'), [
                'is_child' => '1',
                'first_name' => 'Baby',
                'last_name' => 'Mhango',
                'date_of_birth' => now()->subMonth()->toDateString(),
                'sex' => 'male',
            ])
            ->assertSessionHasErrors('mother_id');
    }

    public function test_outpatient_visit_from_check_in_to_dispensing(): void
    {
        $patient = $this->registerAdult();
        $visit = $this->checkInWithVitals($patient);
        $medicine = Medicine::query()->where('facility_id', $visit->facility_id)->where('name', 'Paracetamol')->firstOrFail();
        $stockBefore = $medicine->stock_quantity;

        $this->actingAs($this->user('doctor@healthpassport.mw'))
            ->post(route('consultations.store', $visit), [
                'history' => 'Fever for two days',
                'diagnosis' => 'Viral illness',
                'treatment_plan' => 'Rest and fluids',
                'outcome' => 'send_home',
                'items' => [['medicine_id' => $medicine->id, 'dosage' => '1 g', 'frequency' => 'Three times daily', 'duration_days' => 3, 'quantity' => 18]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(VisitStatus::AwaitingPharmacy, $visit->refresh()->status);
        $prescription = $visit->prescriptions()->firstOrFail();

        $this->actingAs($this->user('pharmacist@healthpassport.mw'))
            ->post(route('pharmacy.dispense', $prescription))
            ->assertSessionHasNoErrors();

        $this->assertSame(PrescriptionStatus::Dispensed, $prescription->refresh()->status);
        $this->assertSame(VisitStatus::Completed, $visit->refresh()->status);
        $this->assertSame($stockBefore - 18, $medicine->refresh()->stock_quantity);

        $this->actingAs($this->user('doctor@healthpassport.mw'))->get(route('visits.report', $visit))->assertOk()->assertSee('Viral illness');
    }

    public function test_inpatient_stay_releases_the_bed_on_discharge(): void
    {
        $patient = $this->registerAdult();
        $visit = $this->checkInWithVitals($patient);

        $this->actingAs($this->user('doctor@healthpassport.mw'))
            ->post(route('consultations.store', $visit), [
                'history' => 'Severe abdominal pain',
                'diagnosis' => 'Appendicitis suspected',
                'outcome' => 'admit',
                'admission_reason' => 'Observation and surgical review',
                'preferred_ward_type' => 'General Medical',
            ])
            ->assertSessionHasNoErrors();

        $admission = Admission::query()->where('patient_id', $patient->id)->firstOrFail();
        $this->assertSame(AdmissionStatus::AwaitingBed, $admission->status);

        $bed = Bed::query()->whereHas('ward', fn ($query) => $query->where('facility_id', $visit->facility_id)->where('name', 'Female Medical Ward'))
            ->where('status', BedStatus::Available)->firstOrFail();

        $this->actingAs($this->user('nurse@healthpassport.mw'))
            ->post(route('admissions.allocate-bed', $admission), ['bed_id' => $bed->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(BedStatus::Occupied, $bed->refresh()->status);

        $this->actingAs($this->user('nurse@healthpassport.mw'))
            ->post(route('admissions.notes', $admission), ['note' => 'Comfortable overnight.'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user('doctor@healthpassport.mw'))
            ->post(route('admissions.discharge.store', $admission), [
                'discharge_outcome' => 'recovered',
                'discharge_summary' => 'Pain resolved without surgery.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(AdmissionStatus::Discharged, $admission->refresh()->status);
        $this->assertSame(BedStatus::Available, $bed->refresh()->status);
        $this->actingAs($this->user('doctor@healthpassport.mw'))->get(route('admissions.report', $admission))->assertOk();
    }

    public function test_a_male_patient_cannot_be_placed_in_a_female_ward(): void
    {
        $patient = Patient::query()->where('first_name', 'Mary')->firstOrFail();
        $admission = Admission::query()->where('status', AdmissionStatus::AwaitingBed)->firstOrFail();
        $maleBed = Bed::query()->whereHas('ward', fn ($query) => $query->where('name', 'Male Medical Ward'))->where('status', BedStatus::Available)->firstOrFail();

        $this->actingAs($this->user('nurse@healthpassport.mw'))
            ->from(route('admissions.show', $admission))
            ->post(route('admissions.allocate-bed', $admission), ['bed_id' => $maleBed->id])
            ->assertSessionHas('error');

        $this->assertSame(BedStatus::Available, $maleBed->refresh()->status);
        $this->assertNotNull($patient);
    }

    public function test_each_role_sees_only_the_records_it_needs(): void
    {
        $patient = Patient::query()->where('first_name', 'John')->firstOrFail();

        $this->actingAs($this->user('doctor@healthpassport.mw'))->get(route('patients.show', $patient))
            ->assertOk()->assertSee('Uncontrolled hypertension');

        $this->actingAs($this->user('clerk@healthpassport.mw'))->get(route('patients.show', $patient))
            ->assertOk()->assertDontSee('Uncontrolled hypertension');

        $this->actingAs($this->user('pharmacist@healthpassport.mw'))->get(route('patients.show', $patient))
            ->assertForbidden();

        $this->actingAs($this->user('patient@healthpassport.mw'))->get(route('patients.show', $patient))
            ->assertForbidden();
    }

    public function test_a_patient_can_book_and_the_facility_can_approve(): void
    {
        $patientUser = $this->user('patient@healthpassport.mw');
        $facility = $this->user('doctor@healthpassport.mw')->facility;
        $date = now()->next('Tuesday');

        $this->actingAs($patientUser)->get(route('portal.appointments.create', ['facility_id' => $facility->id, 'date' => $date->toDateString()]))
            ->assertOk()->assertSee('Dr Chisomo Mvula');

        $this->actingAs($patientUser)->post(route('portal.appointments.store'), [
            'patient_id' => $patientUser->patient_id,
            'facility_id' => $facility->id,
            'appointment_date' => $date->toDateString(),
            'doctor_id' => '',
            'reason' => 'Check up',
        ])->assertSessionHasNoErrors();

        $appointment = $patientUser->patient->appointments()->where('reason', 'Check up')->firstOrFail();
        $this->assertNotNull($appointment->doctor_id);

        $this->actingAs($this->user('facility@healthpassport.mw'))
            ->post(route('appointments.decide', $appointment), ['decision' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $appointment->refresh()->status->value);
        $this->assertTrue($patientUser->notifications()->exists());
    }
}
