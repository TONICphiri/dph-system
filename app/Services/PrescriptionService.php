<?php

namespace App\Services;

use App\Enums\PrescriptionStatus;
use App\Enums\VisitStatus;
use App\Exceptions\WorkflowException;
use App\Models\Admission;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Creates prescriptions and dispenses them from the facility pharmacy.
 */
class PrescriptionService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(Patient $patient, array $items, User $doctor, ?Visit $visit = null, ?Admission $admission = null, ?string $notes = null): Prescription
    {
        if ($items === []) {
            throw new WorkflowException('Add at least one medicine to the prescription.');
        }

        return DB::transaction(function () use ($patient, $items, $doctor, $visit, $admission, $notes) {
            $prescription = Prescription::create([
                'patient_id' => $patient->id,
                'facility_id' => $doctor->facility_id,
                'visit_id' => $visit?->id,
                'admission_id' => $admission?->id,
                'prescribed_by' => $doctor->id,
                'status' => PrescriptionStatus::Pending,
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $medicine = isset($item['medicine_id'])
                    ? Medicine::query()->where('facility_id', $doctor->facility_id)->find($item['medicine_id'])
                    : null;

                $prescription->items()->create([
                    'medicine_id' => $medicine?->id,
                    'medicine_name' => $medicine?->displayName() ?? $item['medicine_name'],
                    'dosage' => $item['dosage'],
                    'frequency' => $item['frequency'],
                    'duration_days' => $item['duration_days'],
                    'quantity' => $item['quantity'],
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }

            $this->audit->record('prescription.created', "Prescribed medication for {$patient->full_name}.", $prescription);

            return $prescription;
        });
    }

    /**
     * Dispense every item and reduce stock. Completes the visit when this was
     * the last pending prescription of an outpatient visit.
     */
    public function dispense(Prescription $prescription, User $pharmacist): void
    {
        if ($prescription->status !== PrescriptionStatus::Pending) {
            throw new WorkflowException('This prescription has already been processed.');
        }

        DB::transaction(function () use ($prescription, $pharmacist) {
            foreach ($prescription->items as $item) {
                if ($item->medicine_id) {
                    $medicine = Medicine::query()->lockForUpdate()->findOrFail($item->medicine_id);

                    if ($medicine->stock_quantity < $item->quantity) {
                        throw new WorkflowException("Not enough stock for {$medicine->displayName()}. Available: {$medicine->stock_quantity}, required: {$item->quantity}.");
                    }

                    $medicine->decrement('stock_quantity', $item->quantity);
                }

                $item->update(['quantity_dispensed' => $item->quantity]);
            }

            $prescription->update([
                'status' => PrescriptionStatus::Dispensed,
                'dispensed_by' => $pharmacist->id,
                'dispensed_at' => now(),
            ]);

            $this->completeVisitIfDone($prescription);
        });

        $this->audit->record('prescription.dispensed', "Dispensed medication to {$prescription->patient->full_name}.", $prescription);
    }

    public function cancel(Prescription $prescription): void
    {
        if ($prescription->status !== PrescriptionStatus::Pending) {
            throw new WorkflowException('Only pending prescriptions can be cancelled.');
        }

        DB::transaction(function () use ($prescription) {
            $prescription->update(['status' => PrescriptionStatus::Cancelled]);
            $this->completeVisitIfDone($prescription);
        });

        $this->audit->record('prescription.cancelled', "Cancelled a prescription for {$prescription->patient->full_name}.", $prescription);
    }

    private function completeVisitIfDone(Prescription $prescription): void
    {
        $visit = $prescription->visit;

        if (! $visit || $visit->status !== VisitStatus::AwaitingPharmacy) {
            return;
        }

        $stillPending = $visit->prescriptions()->where('status', PrescriptionStatus::Pending)->exists();

        if (! $stillPending) {
            $visit->update(['status' => VisitStatus::Completed, 'completed_at' => now()]);
        }
    }
}
