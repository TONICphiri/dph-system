<?php

namespace App\Http\Controllers\Patients;

use App\Enums\PatientStatus;
use App\Enums\Sex;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Models\District;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\AuditLogger;
use App\Services\PatientRegistrationService;
use App\Services\QrCodeService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientRegistrationService $registration,
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $patients = Patient::query()
            ->with(['registeredFacility', 'mother'])
            ->search($request->input('search'))
            ->when($request->filled('sex'), fn ($query) => $query->where('sex', $request->input('sex')))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('patients.index', ['patients' => $patients, 'sexes' => Sex::options()]);
    }

    public function create(Request $request): View
    {
        return view('patients.form', [
            ...$this->formData(new Patient),
            'mother' => $request->filled('mother') ? Patient::query()->find($request->integer('mother')) : null,
        ]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $result = $this->registration->register(
            $request->patientData(),
            $request->contacts(),
            $request->user(),
            $request->boolean('create_portal_account'),
        );

        $patient = $result['patient'];
        $redirect = redirect()->route('patients.show', $patient)
            ->with('success', "{$patient->full_name} has been registered with passport number {$patient->passport_number}.");

        if ($result['temporary_password']) {
            $redirect->with('temporary_password', [
                'name' => $patient->full_name,
                'email' => $patient->email,
                'password' => $result['temporary_password'],
            ]);
        }

        return $redirect;
    }

    /**
     * The patient record. Each section is shown only to roles allowed to see
     * it, as defined in PatientPolicy.
     */
    public function show(Request $request, Patient $patient, QrCodeService $qr): View
    {
        $this->authorize('view', $patient);
        $user = $request->user();

        $patient->load(['district', 'registeredFacility', 'emergencyContacts', 'mother', 'children', 'portalAccount']);

        $data = ['patient' => $patient, 'qrCode' => $qr->forPatient($patient, 132)];

        if ($user->can('viewBasicHistory', $patient)) {
            $data['vitals'] = $patient->vitals()->with('recordedBy')->latest('recorded_at')->limit(10)->get();
            $data['visits'] = $patient->visits()->with(['facility', 'doctor'])->latest('checked_in_at')->limit(15)->get();
            $data['admissions'] = $patient->admissions()->with(['facility', 'ward', 'bed'])->latest('admitted_at')->get();
        }

        if ($user->can('viewFullRecord', $patient)) {
            $data['prescriptions'] = $patient->prescriptions()->with(['items', 'prescriber', 'facility'])->latest()->limit(15)->get();
            $data['reminders'] = $patient->reminders()->latest('due_on')->get();
        }

        if ($user->can('viewVaccinations', $patient)) {
            $data['vaccinations'] = $patient->vaccinations()->with(['vaccine', 'facility'])->latest('administered_on')->get();
            $data['vaccines'] = Vaccine::query()->where('is_active', true)->orderBy('name')->get();
        }

        $data['openVisit'] = $user->facility_id
            ? $patient->visits()->where('facility_id', $user->facility_id)->open()->latest()->first()
            : null;

        $this->audit->record('patient.viewed', "Opened the record of {$patient->full_name}.", $patient);

        return view('patients.show', $data);
    }

    public function edit(Patient $patient): View
    {
        $patient->load('emergencyContacts', 'mother');

        return view('patients.form', [...$this->formData($patient), 'mother' => $patient->mother]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::enum(PatientStatus::class)]])['status'];

        $patient->update([...$request->patientData(), 'status' => $status]);
        $this->registration->syncEmergencyContacts($patient, $request->contacts());
        $this->audit->record('patient.updated', "Updated the personal details of {$patient->full_name}.", $patient);

        return redirect()->route('patients.show', $patient)->with('success', 'The patient details have been saved.');
    }

    public function createPortalAccount(Patient $patient): RedirectResponse
    {
        $password = $this->registration->createPortalAccount($patient);
        $this->audit->record('patient.portal-account-created', "Created a portal account for {$patient->full_name}.", $patient);

        return back()->with('success', 'The patient portal account has been created.')
            ->with('temporary_password', ['name' => $patient->full_name, 'email' => $patient->email, 'password' => $password]);
    }

    /**
     * Printable health passport card with the QR code.
     */
    public function card(Patient $patient, QrCodeService $qr): View
    {
        $this->authorize('printCard', $patient);

        return view('patients.card', [
            'patient' => $patient->load(['registeredFacility', 'emergencyContacts']),
            'qrCode' => $qr->forPatient($patient, 150),
        ]);
    }

    private function formData(Patient $patient): array
    {
        return [
            'patient' => $patient,
            'districts' => District::query()->orderBy('name')->get(),
            'sexes' => Sex::options(),
            'statuses' => PatientStatus::options(),
            'bloodGroups' => config('health_passport.blood_groups'),
            'relationships' => $this->settings->list('relationship_types'),
            'separationAge' => $this->settings->childSeparationAge(),
        ];
    }
}
