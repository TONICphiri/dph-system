<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\AppointmentStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Appointment requests, staff side. Doctors see requests allocated to them;
 * Facility Administrators see every request at the facility.
 */
class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->input('status', AppointmentStatus::Pending->value);

        return view('clinical.appointments', [
            'appointments' => Appointment::query()
                ->with(['patient', 'doctor', 'review'])
                ->where('facility_id', $user->facility_id)
                ->when($user->isRole(RoleName::Doctor), fn ($query) => $query->where('doctor_id', $user->id))
                ->where('status', $status)
                ->when($request->filled('date'), fn ($query) => $query->whereDate('appointment_date', $request->date('date')))
                ->orderBy('appointment_date')
                ->paginate($this->perPage())
                ->withQueryString(),
            'status' => $status,
            'statuses' => AppointmentStatus::options(),
        ]);
    }

    public function decide(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('decide', $appointment);

        $data = $request->validate([
            'decision' => ['required', Rule::in([AppointmentStatus::Approved->value, AppointmentStatus::Declined->value])],
            'decision_note' => ['nullable', 'required_if:decision,'.AppointmentStatus::Declined->value, 'string', 'max:255'],
        ], ['decision_note.required_if' => 'Give the patient a reason when declining an appointment.']);

        $decision = AppointmentStatus::from($data['decision']);
        $this->appointments->decide($appointment, $decision, $data['decision_note'] ?? null, $request->user());

        return back()->with('success', "The appointment has been {$decision->value}. The patient has been notified.");
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        $this->authorize('decide', $appointment);
        $this->appointments->complete($appointment);

        return back()->with('success', 'The appointment is marked as attended.');
    }
}
