<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\DischargeOutcome;
use App\Enums\FacilityStatus;
use App\Enums\PrescriptionStatus;
use App\Enums\WardGender;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionRequest;
use App\Http\Requests\VitalsRequest;
use App\Models\Admission;
use App\Models\Medicine;
use App\Models\PrescriptionItem;
use App\Models\Ward;
use App\Services\AdmissionService;
use App\Services\AuditLogger;
use App\Services\PrescriptionService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function __construct(
        private readonly AdmissionService $admissions,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status', 'current');
        $facilityId = $request->user()->facility_id;

        $admissions = Admission::query()
            ->with(['patient', 'ward', 'bed', 'admittedBy'])
            ->where('facility_id', $facilityId)
            ->when($status === 'current', fn ($query) => $query->current())
            ->when($status !== 'current', fn ($query) => $query->where('status', $status))
            ->when($request->filled('ward'), fn ($query) => $query->where('ward_id', $request->integer('ward')))
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [AdmissionStatus::AwaitingBed->value])
            ->latest('admitted_at')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('clinical.admissions.index', [
            'admissions' => $admissions,
            'status' => $status,
            'statuses' => AdmissionStatus::options(),
            'wards' => Ward::query()->where('facility_id', $facilityId)->orderBy('name')->get(),
        ]);
    }

    /**
     * The inpatient chart: bed, vitals, progress notes and medication.
     */
    public function show(Request $request, Admission $admission, SettingService $settings): View
    {
        $this->authorize('view', $admission);

        $admission->load([
            'patient', 'ward', 'bed', 'admittedBy', 'allocatedBy', 'dischargedBy', 'visit',
            'vitals.recordedBy', 'progressNotes.author', 'prescriptions.items', 'prescriptions.prescriber',
            'medicationAdministrations.item', 'medicationAdministrations.givenBy',
        ]);

        return view('clinical.admissions.show', [
            'admission' => $admission,
            'wards' => $admission->status === AdmissionStatus::AwaitingBed ? $this->wardsForPatient($admission) : collect(),
            'medicines' => Medicine::query()->where('facility_id', $admission->facility_id)->orderBy('name')->get(),
            'frequencies' => $settings->list('dosage_frequencies'),
            'activeItems' => $admission->prescriptions->where('status', '!=', PrescriptionStatus::Cancelled)->flatMap->items,
        ]);
    }

    public function allocateBed(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorize('allocateBed', $admission);
        $bedId = (int) $request->validate(['bed_id' => ['required', 'integer']], ['bed_id.required' => 'Choose a bed.'])['bed_id'];

        $this->admissions->allocateBed($admission, $bedId, $request->user());

        return back()->with('success', 'The bed has been allocated.');
    }

    public function storeVitals(VitalsRequest $request, Admission $admission): RedirectResponse
    {
        $this->authorize('recordVitals', $admission);
        $this->ensureAdmitted($admission);

        $admission->vitals()->create([
            ...$request->validated(),
            'patient_id' => $admission->patient_id,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        return back()->with('success', 'Vital signs recorded.');
    }

    public function storeNote(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorize('writeNote', $admission);
        $this->ensureAdmitted($admission);

        $admission->progressNotes()->create([
            'note' => $request->validate(['note' => ['required', 'string', 'max:5000']])['note'],
            'author_id' => $request->user()->id,
        ]);

        return back()->with('success', 'The progress note has been saved.');
    }

    public function storeMedication(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorize('recordMedication', $admission);
        $this->ensureAdmitted($admission);

        $data = $request->validate([
            'prescription_item_id' => ['required', 'integer'],
            'given_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [], ['prescription_item_id' => 'medicine']);

        $belongs = PrescriptionItem::query()->whereKey($data['prescription_item_id'])
            ->whereHas('prescription', fn ($query) => $query->where('admission_id', $admission->id))->exists();

        if (! $belongs) {
            throw new WorkflowException('The selected medicine is not prescribed for this admission.');
        }

        $admission->medicationAdministrations()->create([...$data, 'given_by' => $request->user()->id]);

        return back()->with('success', 'The dose has been recorded.');
    }

    public function storePrescription(PrescriptionRequest $request, Admission $admission, PrescriptionService $prescriptions): RedirectResponse
    {
        $this->authorize('prescribe', $admission);
        $this->ensureAdmitted($admission);

        $prescriptions->create($admission->patient, $request->prescriptionItems(), $request->user(), null, $admission, $request->input('notes'));

        return back()->with('success', 'The prescription has been sent to the pharmacy.');
    }

    public function dischargeForm(Admission $admission): View
    {
        $this->authorize('discharge', $admission);

        return view('clinical.admissions.discharge', [
            'admission' => $admission->load(['patient', 'ward', 'bed']),
            'outcomes' => DischargeOutcome::options(),
        ]);
    }

    public function discharge(Request $request, Admission $admission): RedirectResponse
    {
        $this->authorize('discharge', $admission);

        $data = $request->validate([
            'discharge_outcome' => ['required', Rule::enum(DischargeOutcome::class)],
            'discharge_summary' => ['required', 'string', 'max:5000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
        ], [], ['discharge_outcome' => 'outcome', 'discharge_summary' => 'discharge summary']);

        $this->admissions->discharge($admission, $data, $request->user());

        return redirect()->route('admissions.report', $admission)->with('success', 'The patient has been discharged and the bed is now available.');
    }

    /**
     * The full inpatient report compiled at discharge.
     */
    public function report(Admission $admission): View
    {
        $this->authorize('viewReport', $admission);

        return view('clinical.admissions.report', [
            'admission' => $admission->load([
                'patient.district', 'facility', 'ward', 'bed', 'visit', 'admittedBy', 'dischargedBy',
                'vitals', 'progressNotes.author', 'prescriptions.items', 'medicationAdministrations.item', 'medicationAdministrations.givenBy',
            ]),
        ]);
    }

    private function wardsForPatient(Admission $admission)
    {
        return Ward::query()
            ->where('facility_id', $admission->facility_id)
            ->where('status', FacilityStatus::Active)
            ->whereIn('gender_restriction', [WardGender::Mixed->value, $admission->patient->sex->value])
            ->when($this->admissions->isAdult($admission), fn ($query) => $query->where('ward_type', '!=', $this->admissions->childrenWardType()))
            ->with(['beds' => fn ($query) => $query->where('status', BedStatus::Available)])
            ->orderByRaw('CASE WHEN ward_type = ? THEN 0 ELSE 1 END', [$admission->preferred_ward_type])
            ->orderBy('name')
            ->get();
    }

    private function ensureAdmitted(Admission $admission): void
    {
        if ($admission->status === AdmissionStatus::Discharged) {
            throw new WorkflowException('This patient has been discharged. The chart can no longer be changed.');
        }
    }
}
