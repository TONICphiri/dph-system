<?php

namespace App\Http\Controllers\Portal;

use App\Enums\FacilityStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Facility;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Appointment booking for patients. The system checks which doctors are
 * working on the chosen day and how many places they have left.
 */
class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    public function index(Request $request): View
    {
        $patient = $request->user()->patient;
        abort_if(! $patient, 403);

        return view('portal.appointments.index', [
            'appointments' => Appointment::query()
                ->with(['facility', 'doctor', 'review'])
                ->whereIn('patient_id', $patient->children()->pluck('id')->push($patient->id))
                ->latest('appointment_date')
                ->paginate($this->perPage()),
        ]);
    }

    public function create(Request $request): View
    {
        $patient = $request->user()->patient->load('children');

        $request->validate([
            'facility_id' => ['nullable', 'exists:facilities,id'],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['date.after_or_equal' => 'Please choose today or a future date.']);

        $facility = $request->filled('facility_id') ? Facility::query()->find($request->integer('facility_id')) : null;
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : null;

        return view('portal.appointments.create', [
            'patient' => $patient,
            'facilities' => Facility::query()->active()->with('district')->orderBy('name')->get(),
            'facility' => $facility,
            'date' => $date,
            'doctors' => $facility && $date ? $this->appointments->availableDoctors($facility, $date) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = $request->user()->patient;
        $allowedPatients = $patient->children()->pluck('id')->push($patient->id)->all();

        $data = $request->validate([
            'patient_id' => ['required', Rule::in($allowedPatients)],
            'facility_id' => ['required', Rule::exists('facilities', 'id')->where('status', FacilityStatus::Active->value)],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'doctor_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ], [], ['patient_id' => 'patient', 'facility_id' => 'facility', 'appointment_date' => 'date']);

        $appointment = $this->appointments->book(
            $patient->newQuery()->findOrFail($data['patient_id']),
            Facility::findOrFail($data['facility_id']),
            Carbon::parse($data['appointment_date']),
            isset($data['doctor_id']) ? (int) $data['doctor_id'] : null,
            $data['reason'],
        );

        return redirect()->route('portal.appointments.index')
            ->with('success', "Your request has been sent. You will be notified when {$appointment->facility->name} approves it.");
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorize('cancel', $appointment);
        $this->appointments->cancel($appointment);

        return back()->with('success', 'The appointment has been cancelled.');
    }

    public function review(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('review', $appointment);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'would_recommend' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [], ['would_recommend' => 'recommendation']);

        $this->appointments->review($appointment, (int) $data['rating'], (bool) $data['would_recommend'], $data['comment'] ?? null);

        return back()->with('success', 'Thank you. Your feedback has been saved.');
    }
}
