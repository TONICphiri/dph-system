<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\PrescriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\PrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The pharmacy sees prescriptions and dosage only, not the full record.
 */
class PharmacyController extends Controller
{
    public function __construct(private readonly PrescriptionService $prescriptions)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->input('status', PrescriptionStatus::Pending->value);

        return view('clinical.pharmacy.index', [
            'prescriptions' => Prescription::query()
                ->with(['patient', 'items', 'prescriber', 'admission.ward'])
                ->where('facility_id', $request->user()->facility_id)
                ->where('status', $status)
                ->when($request->filled('search'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->search($request->input('search'))))
                ->orderBy($status === PrescriptionStatus::Pending->value ? 'created_at' : 'dispensed_at', $status === PrescriptionStatus::Pending->value ? 'asc' : 'desc')
                ->paginate($this->perPage())
                ->withQueryString(),
            'status' => $status,
            'statuses' => PrescriptionStatus::options(),
        ]);
    }

    public function show(Prescription $prescription): View
    {
        $this->authorize('dispense', $prescription);

        return view('clinical.pharmacy.show', [
            'prescription' => $prescription->load(['patient', 'items.medicine', 'prescriber', 'dispenser']),
        ]);
    }

    public function dispense(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorize('dispense', $prescription);
        $this->prescriptions->dispense($prescription, $request->user());

        return redirect()->route('pharmacy.index')->with('success', "Medication dispensed to {$prescription->patient->full_name}.");
    }

    public function cancel(Prescription $prescription): RedirectResponse
    {
        $this->authorize('dispense', $prescription);
        $this->prescriptions->cancel($prescription);

        return redirect()->route('pharmacy.index')->with('success', 'The prescription has been cancelled.');
    }
}
