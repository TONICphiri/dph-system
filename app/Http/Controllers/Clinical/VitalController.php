<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\VitalsRequest;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VitalController extends Controller
{
    public function create(Visit $visit): View
    {
        $this->authorize('recordVitals', $visit);

        return view('clinical.vitals', [
            'visit' => $visit->load('patient'),
            'previous' => $visit->patient->vitals()->latest('recorded_at')->first(),
        ]);
    }

    public function store(VitalsRequest $request, Visit $visit, VisitService $visits): RedirectResponse
    {
        $this->authorize('recordVitals', $visit);
        $visits->recordVitals($visit, $request->validated(), $request->user());

        return redirect()->route('visits.queue')->with('success', "Vital signs recorded. {$visit->patient->full_name} is now waiting for the doctor.");
    }
}
