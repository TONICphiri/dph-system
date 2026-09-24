<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\VaccinationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VaccinationController extends Controller
{
    public function create(Patient $patient): View
    {
        return view('clinical.vaccination', [
            'patient' => $patient,
            'vaccines' => Vaccine::query()->where('is_active', true)->orderBy('name')->get(),
            'given' => $patient->vaccinations()->with('vaccine')->latest('administered_on')->get(),
        ]);
    }

    public function store(Request $request, Patient $patient, VaccinationService $vaccinations): RedirectResponse
    {
        $data = $request->validate([
            'vaccine_id' => ['required', Rule::exists('vaccines', 'id')->where('is_active', true)],
            'administered_on' => ['required', 'date', 'before_or_equal:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], ['vaccine_id' => 'vaccine', 'administered_on' => 'date given']);

        $vaccination = $vaccinations->record($patient, Vaccine::findOrFail($data['vaccine_id']), $data, $request->user());

        $message = "Dose {$vaccination->dose_number} of {$vaccination->vaccine->name} recorded.";

        if ($vaccination->next_dose_due_on) {
            $message .= ' A reminder has been set for '.$vaccination->next_dose_due_on->format('j F Y').'.';
        }

        return redirect()->route('patients.show', $patient)->with('success', $message);
    }
}
