<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\BedStatus;
use App\Enums\CampaignAudience;
use App\Enums\DischargeOutcome;
use App\Enums\FacilityStatus;
use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Enums\WardGender;
use App\Models\Appointment;
use App\Models\AppointmentReview;
use App\Models\District;
use App\Models\DoctorSchedule;
use App\Models\Facility;
use App\Models\HealthCampaign;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccine;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\ConsultationService;
use App\Services\PatientRegistrationService;
use App\Services\PrescriptionService;
use App\Services\VaccinationService;
use App\Services\VisitService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demonstration data for presentations and testing. Records are created
 * through the same services the screens use, so every business rule applies.
 * All demonstration accounts share the password in ADMIN_PASSWORD.
 */
class DemoDataSeeder extends Seeder
{
    private string $password;

    public function __construct(
        private readonly PatientRegistrationService $registration,
        private readonly VisitService $visits,
        private readonly ConsultationService $consultations,
        private readonly PrescriptionService $prescriptions,
        private readonly AdmissionService $admissions,
        private readonly VaccinationService $vaccinations,
    ) {
        $this->password = config('health_passport.admin.password');
    }

    public function run(): void
    {
        if (Facility::query()->exists()) {
            $this->command?->warn('Demonstration data already exists. Skipped.');

            return;
        }

        $ndirande = $this->facility('Ndirande Community Hospital', 'NDH-001', 'Community Hospital', 'Government', 'Blantyre', 'Ndirande Township, Blantyre', '+265 1 870 212');
        $zomba = $this->facility('Zomba Central Hospital', 'ZCH-001', 'Central Hospital', 'Government', 'Zomba', 'Kamuzu Highway, Zomba', '+265 1 527 050');

        $staff = [
            'facility_admin' => $this->staff($ndirande, RoleName::FacilityAdmin, 'Martha Kumwenda', 'facility@healthpassport.mw', 'Hospital Administrator'),
            'clerk' => $this->staff($ndirande, RoleName::Clerk, 'Joseph Mbewe', 'clerk@healthpassport.mw', 'Registration Clerk'),
            'nurse' => $this->staff($ndirande, RoleName::Nurse, 'Alinafe Gondwe', 'nurse@healthpassport.mw', 'Registered Nurse', 'NMCM-20417'),
            'doctor' => $this->staff($ndirande, RoleName::Doctor, 'Dr Chisomo Mvula', 'doctor@healthpassport.mw', 'Medical Officer', 'MCM-11820'),
            'doctor_two' => $this->staff($ndirande, RoleName::Doctor, 'Dr Ruth Kachingwe', 'doctor2@healthpassport.mw', 'Clinical Officer', 'MCM-13055'),
            'pharmacist' => $this->staff($ndirande, RoleName::Pharmacist, 'Henry Chikopa', 'pharmacist@healthpassport.mw', 'Pharmacist', 'PMRA-0931'),
        ];
        $this->staff($zomba, RoleName::FacilityAdmin, 'Lucy Nyirenda', 'zomba.admin@healthpassport.mw', 'Hospital Administrator');
        $zombaDoctor = $this->staff($zomba, RoleName::Doctor, 'Dr Samuel Banda', 'zomba.doctor@healthpassport.mw', 'Physician', 'MCM-09211');

        $this->wards($ndirande, [
            ['Male Medical Ward', 'General Medical', WardGender::Male, 10],
            ['Female Medical Ward', 'General Medical', WardGender::Female, 10],
            ['Maternity Ward', 'Maternity', WardGender::Female, 8],
            ['Children Ward', 'Paediatric', WardGender::Mixed, 8],
        ]);
        $this->wards($zomba, [
            ['Surgical Ward', 'Surgical', WardGender::Mixed, 12],
            ['Intensive Care Unit', 'Intensive Care', WardGender::Mixed, 4],
        ]);

        $this->medicines($ndirande);
        $this->medicines($zomba);

        foreach ([$staff['doctor'], $staff['doctor_two']] as $doctor) {
            $this->schedule($ndirande, $doctor, [1, 2, 3, 4, 5], '08:00', '16:00', 12);
        }
        $this->schedule($zomba, $zombaDoctor, [1, 3, 5], '08:00', '13:00', 8);

        $this->patientsAndWorkflow($ndirande, $staff);
        $this->campaign($staff['facility_admin'], $ndirande);

        auth()->logout();
    }

    private function facility(string $name, string $code, string $type, string $ownership, string $district, string $address, string $phone): Facility
    {
        return Facility::create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'ownership' => $ownership,
            'district_id' => District::query()->where('name', $district)->value('id'),
            'physical_address' => $address,
            'phone' => $phone,
            'email' => strtolower(str_replace(' ', '.', $name)).'@health.gov.mw',
            'status' => FacilityStatus::Active,
        ]);
    }

    private function staff(Facility $facility, RoleName $role, string $name, string $email, string $title, ?string $registration = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => '+265 99'.random_int(1000000, 9999999),
            'job_title' => $title,
            'professional_registration_number' => $registration,
            'facility_id' => $facility->id,
            'status' => UserStatus::Active,
            'must_change_password' => false,
            'password' => $this->password,
        ]);

        return tap($user)->assignRole($role->value);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: WardGender, 3: int}>  $wards
     */
    private function wards(Facility $facility, array $wards): void
    {
        foreach ($wards as [$name, $type, $gender, $beds]) {
            $ward = Ward::create([
                'facility_id' => $facility->id,
                'name' => $name,
                'ward_type' => $type,
                'gender_restriction' => $gender,
                'status' => FacilityStatus::Active,
            ]);

            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 2));

            for ($number = 1; $number <= $beds; $number++) {
                $ward->beds()->create([
                    'bed_number' => $prefix.'-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                    'status' => $number === $beds ? BedStatus::Maintenance : BedStatus::Available,
                ]);
            }
        }
    }

    private function medicines(Facility $facility): void
    {
        $medicines = [
            ['Paracetamol', '500 mg', 'Tablet', 2400, 500],
            ['Amoxicillin', '500 mg', 'Capsule', 1200, 300],
            ['Lumefantrine and Artemether', '20/120 mg', 'Tablet', 900, 240],
            ['Metformin', '500 mg', 'Tablet', 800, 200],
            ['Amlodipine', '5 mg', 'Tablet', 150, 200],
            ['Oral Rehydration Salts', '20.5 g', 'Suspension', 400, 100],
            ['Ceftriaxone', '1 g', 'Injection', 60, 50],
            ['Tenofovir, Lamivudine and Dolutegravir', '300/300/50 mg', 'Tablet', 600, 180],
            ['Ferrous Sulphate', '200 mg', 'Tablet', 1000, 300],
            ['Ibuprofen', '400 mg', 'Tablet', 40, 200],
        ];

        foreach ($medicines as [$name, $strength, $form, $stock, $reorder]) {
            Medicine::create([
                'facility_id' => $facility->id,
                'name' => $name,
                'strength' => $strength,
                'dosage_form' => $form,
                'stock_quantity' => $stock,
                'reorder_level' => $reorder,
                'expiry_date' => now()->addMonths(random_int(8, 30))->startOfMonth(),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $days  Day numbers, 1 for Monday to 7 for Sunday.
     */
    private function schedule(Facility $facility, User $doctor, array $days, string $start, string $end, int $places): void
    {
        foreach ($days as $day) {
            DoctorSchedule::create([
                'facility_id' => $facility->id,
                'doctor_id' => $doctor->id,
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'max_appointments' => $places,
            ]);
        }
    }

    /**
     * @param  array<string, User>  $staff
     */
    private function patientsAndWorkflow(Facility $facility, array $staff): void
    {
        auth()->setUser($staff['clerk']);

        $blantyre = \App\Models\District::query()->where('name', 'Blantyre')->value('id');
        $contact = fn (string $name, string $relationship, string $phone) => [['full_name' => $name, 'relationship' => $relationship, 'phone' => $phone, 'physical_address' => null]];

        // A mother with a portal account and a young child linked to her record.
        $grace = $this->register([
            'district_id' => $blantyre, 'national_id' => 'KT7Y4M21', 'first_name' => 'Grace', 'last_name' => 'Banda', 'date_of_birth' => '1994-03-12', 'sex' => 'female',
            'phone' => '+265 991 204 118', 'email' => 'patient@healthpassport.mw', 'village' => 'Ndirande', 'traditional_authority' => 'Kapeni',
            'physical_address' => 'Ndirande Township, House 42', 'occupation' => 'Teacher', 'blood_group' => 'O+',
            'allergies' => 'Penicillin', 'chronic_conditions' => 'HIV, on antiretroviral therapy',
        ], $contact('Thomas Banda', 'Spouse', '+265 888 310 442'), $staff['clerk'], portal: true);

        $daniel = $this->register([
            'district_id' => $blantyre, 'mother_id' => $grace->id, 'first_name' => 'Daniel', 'last_name' => 'Banda', 'date_of_birth' => now()->subMonths(3)->toDateString(), 'sex' => 'male',
            'village' => 'Ndirande', 'physical_address' => 'Ndirande Township, House 42', 'blood_group' => 'O+',
        ], $contact('Grace Banda', 'Mother', '+265 991 204 118'), $staff['clerk']);

        $john = $this->register([
            'district_id' => $blantyre, 'national_id' => 'PL2Q8X55', 'first_name' => 'John', 'last_name' => 'Phiri', 'date_of_birth' => '1978-11-02', 'sex' => 'male',
            'phone' => '+265 999 552 870', 'village' => 'Chilomoni', 'occupation' => 'Driver', 'blood_group' => 'A+', 'chronic_conditions' => 'Hypertension',
        ], $contact('Esther Phiri', 'Spouse', '+265 881 220 961'), $staff['clerk']);

        $mary = $this->register([
            'district_id' => $blantyre, 'national_id' => 'MW4R6T90', 'first_name' => 'Mary', 'last_name' => 'Mwale', 'date_of_birth' => '1989-06-24', 'sex' => 'female',
            'phone' => '+265 995 118 004', 'village' => 'Bangwe', 'occupation' => 'Trader', 'blood_group' => 'B+',
        ], $contact('Paul Mwale', 'Brother', '+265 884 773 120'), $staff['clerk']);

        $peter = $this->register([
            'district_id' => $blantyre, 'national_id' => 'ZX9C3V12', 'first_name' => 'Peter', 'last_name' => 'Chirwa', 'date_of_birth' => '1965-01-15', 'sex' => 'male',
            'phone' => '+265 997 002 318', 'village' => 'Limbe', 'occupation' => 'Farmer', 'blood_group' => 'AB+', 'chronic_conditions' => 'Type 2 diabetes',
        ], $contact('Anne Chirwa', 'Daughter', '+265 882 640 555'), $staff['clerk']);

        $esther = $this->register([
            'district_id' => $blantyre, 'national_id' => 'QN5B7L33', 'first_name' => 'Esther', 'last_name' => 'Nkhoma', 'date_of_birth' => '2001-09-30', 'sex' => 'female',
            'phone' => '+265 996 481 227', 'village' => 'Chinyonga', 'occupation' => 'Student', 'blood_group' => 'O-',
        ], $contact('Rose Nkhoma', 'Mother', '+265 885 914 002'), $staff['clerk']);

        $james = $this->register([
            'district_id' => $blantyre, 'national_id' => 'HD8S2K47', 'first_name' => 'James', 'last_name' => 'Tembo', 'date_of_birth' => '1958-04-08', 'sex' => 'male',
            'phone' => '+265 993 660 145', 'village' => 'Machinjiri', 'occupation' => 'Retired', 'blood_group' => 'A-',
        ], $contact('Joyce Tembo', 'Spouse', '+265 887 305 610'), $staff['clerk']);

        $ruth = $this->register([
            'district_id' => $blantyre, 'national_id' => 'YT3F9W68', 'first_name' => 'Ruth', 'last_name' => 'Kalua', 'date_of_birth' => '1992-12-19', 'sex' => 'female',
            'phone' => '+265 998 725 034', 'village' => 'Chilobwe', 'occupation' => 'Nurse aide', 'blood_group' => 'B-',
        ], $contact('Moses Kalua', 'Spouse', '+265 889 117 850'), $staff['clerk']);

        // Completed outpatient visit two weeks ago: consultation, pharmacy, discharge.
        $this->at(now()->subDays(14)->setTime(9, 15), function () use ($john, $staff) {
            $visit = $this->visitWithVitals($john, 'Headache and dizziness', $staff, ['temperature' => 36.8, 'weight' => 82.0, 'height' => 174.0, 'systolic_pressure' => 162, 'diastolic_pressure' => 98, 'pulse_rate' => 84, 'respiratory_rate' => 16, 'oxygen_saturation' => 98]);
            Carbon::setTestNow(now()->addMinutes(35));
            auth()->setUser($staff['doctor']);
            $this->consultations->complete($visit, [
                'history' => 'Headaches for one week. Known hypertension, missed medication for two weeks.',
                'examination' => 'Alert, no neurological signs. Blood pressure raised.',
                'diagnosis' => 'Uncontrolled hypertension',
                'treatment_plan' => 'Restart amlodipine. Reduce salt intake. Review in one month.',
            ], [$this->item($visit->facility_id, 'Amlodipine', '1 tablet', 'Once daily', 30, 30)], ConsultationService::OUTCOME_SEND_HOME, $staff['doctor']);
            Carbon::setTestNow(now()->addMinutes(20));
            auth()->setUser($staff['pharmacist']);
            $this->prescriptions->dispense($visit->refresh()->prescriptions()->first(), $staff['pharmacist']);
        });

        // Postnatal review for the portal patient, matching her completed appointment.
        $this->at(now()->subDays(20)->setTime(10, 30), function () use ($grace, $staff) {
            $visit = $this->visitWithVitals($grace, 'Postnatal review', $staff, ['temperature' => 36.6, 'weight' => 63.0, 'systolic_pressure' => 118, 'diastolic_pressure' => 76, 'pulse_rate' => 76, 'respiratory_rate' => 16, 'oxygen_saturation' => 99]);
            Carbon::setTestNow(now()->addMinutes(35));
            auth()->setUser($staff['doctor']);
            $this->consultations->complete($visit, [
                'history' => 'Ten weeks after delivery. Feeling well, breastfeeding without problems.',
                'examination' => 'Well, not pale. Blood pressure normal.',
                'diagnosis' => 'Normal postnatal recovery',
                'treatment_plan' => 'Continue iron supplements for one month. Family planning counselling given.',
            ], [$this->item($visit->facility_id, 'Ferrous Sulphate', '1 tablet', 'Once daily', 30, 30)], ConsultationService::OUTCOME_SEND_HOME, $staff['doctor']);
            Carbon::setTestNow(now()->addMinutes(20));
            auth()->setUser($staff['pharmacist']);
            $this->prescriptions->dispense($visit->refresh()->prescriptions()->first(), $staff['pharmacist']);
        });

        // Completed inpatient stay last month with discharge report.
        $this->at(now()->subDays(30)->setTime(10, 0), function () use ($esther, $staff) {
            $visit = $this->visitWithVitals($esther, 'High fever and vomiting', $staff, ['temperature' => 39.4, 'weight' => 58.0, 'systolic_pressure' => 104, 'diastolic_pressure' => 66, 'pulse_rate' => 112, 'respiratory_rate' => 22, 'oxygen_saturation' => 96]);
            Carbon::setTestNow(now()->addMinutes(35));
            auth()->setUser($staff['doctor']);
            $this->consultations->complete($visit, [
                'history' => 'Fever for three days with vomiting. Unable to keep oral medication.',
                'examination' => 'Dehydrated, febrile. Malaria rapid test positive.',
                'diagnosis' => 'Severe malaria',
                'treatment_plan' => 'Admit for intravenous treatment and fluids.',
            ], [], ConsultationService::OUTCOME_ADMIT, $staff['doctor'], ['admission_reason' => 'Severe malaria with vomiting', 'preferred_ward_type' => 'General Medical']);
            $admission = $esther->admissions()->latest('id')->first();
            auth()->setUser($staff['facility_admin']);
            $this->admissions->allocateBed($admission, $this->freeBed($visit->facility_id, 'Female Medical Ward'), $staff['facility_admin']);
            $admission->progressNotes()->create(['author_id' => $staff['nurse']->id, 'note' => 'Intravenous fluids running well. Temperature settling.']);
            $this->at(now()->addDays(3), function () use ($admission, $staff) {
                auth()->setUser($staff['doctor']);
                $this->admissions->discharge($admission->refresh(), [
                    'discharge_outcome' => DischargeOutcome::Recovered->value,
                    'discharge_summary' => 'Treated with intravenous artesunate then oral lumefantrine and artemether. Fever resolved, eating well.',
                    'follow_up_instructions' => 'Complete oral treatment. Sleep under a treated mosquito net. Return if fever comes back.',
                ], $staff['doctor']);
            });
        });

        // Today's work queue at different stages.
        $this->at(now()->subMinutes(95), function () use ($mary, $peter, $james, $ruth, $staff) {
            auth()->setUser($staff['clerk']);
            $this->visits->checkIn($mary, 'Cough and chest pain', $staff['clerk']);

            $this->visitWithVitals($peter, 'Routine diabetes review', $staff, ['temperature' => 36.5, 'weight' => 91.5, 'height' => 170.0, 'systolic_pressure' => 138, 'diastolic_pressure' => 86, 'pulse_rate' => 78, 'respiratory_rate' => 16, 'oxygen_saturation' => 97, 'notes' => 'Blood sugar 11.2 mmol per litre']);

            // Admitted and in a bed, with daily notes.
            $visit = $this->visitWithVitals($james, 'Difficulty in breathing', $staff, ['temperature' => 38.6, 'weight' => 70.0, 'systolic_pressure' => 128, 'diastolic_pressure' => 80, 'pulse_rate' => 104, 'respiratory_rate' => 28, 'oxygen_saturation' => 89]);
            Carbon::setTestNow(now()->addMinutes(35));
            auth()->setUser($staff['doctor']);
            $this->consultations->complete($visit, [
                'history' => 'Cough with fever for five days, breathless since yesterday.',
                'examination' => 'Crackles in the right lower chest. Low oxygen saturation.',
                'diagnosis' => 'Community acquired pneumonia',
                'treatment_plan' => 'Admit. Oxygen, intravenous ceftriaxone, review daily.',
            ], [$this->item($visit->facility_id, 'Ceftriaxone', '1 g by injection', 'Once daily', 5, 5)], ConsultationService::OUTCOME_ADMIT, $staff['doctor'], ['admission_reason' => 'Pneumonia needing oxygen', 'preferred_ward_type' => 'General Medical']);
            $admission = $james->admissions()->latest('id')->first();
            auth()->setUser($staff['nurse']);
            $this->admissions->allocateBed($admission, $this->freeBed($visit->facility_id, 'Male Medical Ward'), $staff['nurse']);
            $admission->progressNotes()->create(['author_id' => $staff['nurse']->id, 'note' => 'Oxygen started at 2 litres per minute. Patient comfortable.']);

            // Admitted and waiting for a bed.
            $visit = $this->visitWithVitals($ruth, 'Abdominal pain in pregnancy', $staff, ['temperature' => 37.1, 'weight' => 66.0, 'systolic_pressure' => 118, 'diastolic_pressure' => 74, 'pulse_rate' => 92, 'respiratory_rate' => 18, 'oxygen_saturation' => 98]);
            auth()->setUser($staff['doctor_two']);
            $this->consultations->complete($visit, [
                'history' => '32 weeks pregnant with lower abdominal pain since the morning.',
                'examination' => 'Mild tenderness. Foetal heart rate normal.',
                'diagnosis' => 'Threatened preterm labour',
                'treatment_plan' => 'Admit to maternity for monitoring.',
            ], [], ConsultationService::OUTCOME_ADMIT, $staff['doctor_two'], ['admission_reason' => 'Monitoring for preterm labour', 'preferred_ward_type' => 'Maternity']);
        });

        // Child vaccinations, with the next dose reminder created automatically.
        $this->at(now()->subMonths(3)->addDay()->setTime(9, 0), function () use ($daniel, $staff) {
            auth()->setUser($staff['nurse']);
            foreach (['BCG', 'Oral Polio Vaccine'] as $name) {
                $this->vaccinations->record($daniel, Vaccine::query()->where('name', $name)->firstOrFail(), ['administered_on' => now()->toDateString(), 'batch_number' => 'MW'.random_int(10000, 99999)], $staff['nurse']);
            }
        });

        // Confidential medication reminder: the notification does not name the treatment.
        Reminder::create([
            'patient_id' => $grace->id,
            'category' => ReminderCategory::Medication,
            'title' => 'Antiretroviral medication refill',
            'message' => 'Collect your next three month supply at the antiretroviral therapy clinic.',
            'due_on' => now()->addDays(5)->toDateString(),
            'repeat_every_days' => 90,
            'is_confidential' => true,
            'status' => ReminderStatus::Active,
            'created_by' => $staff['doctor']->id,
        ]);

        // Appointments: one waiting for approval, one approved, one completed and rated.
        $nextWeekday = fn (int $days) => tap(now()->addDays($days), fn (Carbon $date) => $date->isWeekend() ? $date->next(Carbon::MONDAY) : $date);
        Appointment::create(['patient_id' => $peter->id, 'facility_id' => $facility->id, 'doctor_id' => $staff['doctor']->id, 'appointment_date' => $nextWeekday(3)->toDateString(), 'reason' => 'Diabetes follow up', 'status' => AppointmentStatus::Pending]);
        Appointment::create(['patient_id' => $grace->id, 'facility_id' => $facility->id, 'doctor_id' => $staff['doctor_two']->id, 'appointment_date' => $nextWeekday(7)->toDateString(), 'reason' => 'Child growth monitoring for Daniel', 'status' => AppointmentStatus::Approved, 'decided_by' => $staff['facility_admin']->id, 'decided_at' => now()]);
        $past = Appointment::create(['patient_id' => $grace->id, 'facility_id' => $facility->id, 'doctor_id' => $staff['doctor']->id, 'appointment_date' => now()->subDays(20)->toDateString(), 'reason' => 'Postnatal review', 'status' => AppointmentStatus::Completed, 'decided_by' => $staff['facility_admin']->id, 'decided_at' => now()->subDays(22)]);
        AppointmentReview::create(['appointment_id' => $past->id, 'doctor_id' => $staff['doctor']->id, 'rating' => 5, 'would_recommend' => true, 'comment' => 'Clear explanations and short waiting time.']);
    }

    private function campaign(User $author, Facility $facility): void
    {
        HealthCampaign::create([
            'facility_id' => $facility->id,
            'title' => 'Free cervical cancer screening',
            'category' => 'Cancer screening',
            'message' => 'Free cervical cancer screening for women aged 25 to 49 every Thursday this month at Ndirande Community Hospital.',
            'audience' => CampaignAudience::FemalePatients,
            'created_by' => $author->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $contacts
     */
    private function register(array $data, array $contacts, User $clerk, bool $portal = false): Patient
    {
        $patient = $this->registration->register($data, $contacts, $clerk, $portal)['patient'];

        if ($portal) {
            $patient->portalAccount->update(['password' => $this->password, 'must_change_password' => false]);
        }

        return $patient;
    }

    /**
     * @param  array<string, mixed>  $vitals
     * @param  array<string, User>  $staff
     */
    private function visitWithVitals(Patient $patient, string $reason, array $staff, array $vitals): \App\Models\Visit
    {
        auth()->setUser($staff['clerk']);
        $visit = $this->visits->checkIn($patient, $reason, $staff['clerk']);
        auth()->setUser($staff['nurse']);
        $this->visits->recordVitals($visit, $vitals, $staff['nurse']);

        return $visit->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function item(int $facilityId, string $name, string $dosage, string $frequency, int $days, int $quantity): array
    {
        return [
            'medicine_id' => Medicine::query()->where('facility_id', $facilityId)->where('name', $name)->value('id'),
            'medicine_name' => $name,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'duration_days' => $days,
            'quantity' => $quantity,
            'instructions' => null,
        ];
    }

    private function freeBed(int $facilityId, string $wardName): int
    {
        return Ward::query()->where('facility_id', $facilityId)->where('name', $wardName)->firstOrFail()
            ->beds()->where('status', BedStatus::Available)->orderBy('bed_number')->value('id');
    }

    /**
     * Runs the callback as if it were happening at the given time, so the
     * demonstration history has realistic dates.
     */
    private function at(Carbon $moment, callable $callback): void
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow($moment);

        try {
            $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
