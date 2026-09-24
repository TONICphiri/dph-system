<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ReminderStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The patient's own health records, and those of children linked to them.
 */
class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('portal.records', [
            'owner' => $owner,
            'patient' => $patient->load(['emergencyContacts', 'registeredFacility']),
            'family' => collect([$owner])->merge($owner->children),
            'visits' => $patient->visits()->with(['facility', 'doctor', 'vitals'])->latest('checked_in_at')->get(),
            'admissions' => $patient->admissions()->with(['facility', 'ward'])->latest('admitted_at')->get(),
            'prescriptions' => $patient->prescriptions()->with(['items', 'facility'])->latest()->limit(10)->get(),
            'vaccinations' => $patient->vaccinations()->with(['vaccine', 'facility'])->latest('administered_on')->get(),
            'reminders' => $patient->reminders()->where('status', ReminderStatus::Active)->orderBy('due_on')->get(),
        ]);
    }

    public function card(Request $request, QrCodeService $qr): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('patients.card', [
            'patient' => $patient->load(['registeredFacility', 'emergencyContacts']),
            'qrCode' => $qr->forPatient($patient, 150),
        ]);
    }

    private function owner(Request $request): Patient
    {
        $patient = $request->user()->patient;
        abort_if(! $patient, 403, 'This account is not linked to a health passport.');

        return $patient->load('children');
    }

    /**
     * A mother may switch to the record of a linked child.
     */
    private function selectedPatient(Request $request, Patient $owner): Patient
    {
        if (! $request->filled('patient')) {
            return $owner;
        }

        $child = $owner->children->firstWhere('id', $request->integer('patient'));
        abort_if(! $child, 403, 'You can only view your own record and the records of your children.');

        return $child;
    }
}
