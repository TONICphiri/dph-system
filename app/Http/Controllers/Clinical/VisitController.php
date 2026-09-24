<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $reason = $request->validate(['reason_for_visit' => ['required', 'string', 'max:255']])['reason_for_visit'];

        $this->visits->checkIn($patient, $reason, $request->user());

        return redirect()->route('visits.queue')->with('success', "{$patient->full_name} has been checked in and is waiting for vital signs.");
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->authorize('cancel', $visit);
        $this->visits->cancel($visit);

        return back()->with('success', 'The visit has been cancelled.');
    }

    /**
     * Today's outpatient queue at the user's facility, grouped by step.
     */
    public function queue(Request $request): View
    {
        $visits = Visit::query()
            ->with(['patient', 'vitals'])
            ->where('facility_id', $request->user()->facility_id)
            ->whereIn('status', [VisitStatus::WaitingForVitals, VisitStatus::WaitingForDoctor, VisitStatus::AwaitingPharmacy])
            ->oldest('checked_in_at')
            ->get()
            ->groupBy(fn (Visit $visit) => $visit->status->value);

        return view('clinical.queue', [
            'waitingForVitals' => $visits->get(VisitStatus::WaitingForVitals->value, collect()),
            'waitingForDoctor' => $visits->get(VisitStatus::WaitingForDoctor->value, collect()),
            'awaitingPharmacy' => $visits->get(VisitStatus::AwaitingPharmacy->value, collect()),
        ]);
    }

    /**
     * The electronic outpatient visit report.
     */
    public function report(Visit $visit): View
    {
        $this->authorize('viewReport', $visit);

        return view('clinical.visit-report', [
            'visit' => $visit->load(['patient.district', 'facility', 'doctor', 'checkedInBy', 'vitals.recordedBy', 'prescriptions.items', 'prescriptions.dispenser', 'admission']),
        ]);
    }
}
