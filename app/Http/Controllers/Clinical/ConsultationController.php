<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConsultationRequest;
use App\Models\Medicine;
use App\Models\Visit;
use App\Services\ConsultationService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function create(Visit $visit, SettingService $settings): View
    {
        $this->authorize('consult', $visit);
        $patient = $visit->patient->load(['emergencyContacts']);

        return view('clinical.consultation', [
            'visit' => $visit->load('vitals'),
            'patient' => $patient,
            'history' => $patient->visits()->with(['facility', 'doctor'])->whereKeyNot($visit->id)
                ->whereNotNull('diagnosis')->latest('checked_in_at')->limit(5)->get(),
            'medicines' => Medicine::query()->where('facility_id', $visit->facility_id)->orderBy('name')->get(),
            'wardTypes' => $settings->list('ward_types'),
            'frequencies' => $settings->list('dosage_frequencies'),
        ]);
    }

    public function store(ConsultationRequest $request, Visit $visit, ConsultationService $consultations): RedirectResponse
    {
        $this->authorize('consult', $visit);

        $consultations->complete(
            $visit,
            $request->notes(),
            $request->prescriptionItems(),
            $request->input('outcome'),
            $request->user(),
            $request->admission(),
        );

        $visit->refresh();

        $message = match (true) {
            $visit->admission !== null => "{$visit->patient->full_name} has been admitted and is waiting for a bed.",
            $visit->prescriptions()->exists() => 'The consultation is saved. The prescription has been sent to the pharmacy.',
            default => 'The consultation is saved and the visit is complete.',
        };

        return redirect()->route('dashboard')->with('success', $message);
    }
}
