<?php

namespace App\Services;

use App\Enums\CareType;
use App\Enums\VisitStatus;
use App\Exceptions\WorkflowException;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Saves the doctor's consultation and moves the visit to the next step:
 * send home, send to pharmacy, or admit as an inpatient.
 */
class ConsultationService
{
    public const OUTCOME_SEND_HOME = 'send_home';

    public const OUTCOME_ADMIT = 'admit';

    public function __construct(
        private readonly PrescriptionService $prescriptions,
        private readonly AdmissionService $admissions,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $notes  History, examination, diagnosis and plan.
     * @param  array<int, array<string, mixed>>  $items  Prescription items, may be empty.
     * @param  array<string, mixed>  $admission  Admission reason and ward type when admitting.
     */
    public function complete(Visit $visit, array $notes, array $items, string $outcome, User $doctor, array $admission = []): void
    {
        if (! in_array($visit->status, [VisitStatus::WaitingForVitals, VisitStatus::WaitingForDoctor], true)) {
            throw new WorkflowException('This visit is not waiting for a consultation.');
        }

        DB::transaction(function () use ($visit, $notes, $items, $outcome, $doctor, $admission) {
            $visit->update([
                ...$notes,
                'doctor_id' => $doctor->id,
                'consulted_at' => now(),
            ]);

            if ($outcome === self::OUTCOME_ADMIT) {
                $newAdmission = $this->admissions->admit($visit, $admission['admission_reason'], $admission['preferred_ward_type'] ?? null, $doctor);

                if ($items !== []) {
                    $this->prescriptions->create($visit->patient, $items, $doctor, null, $newAdmission);
                }

                return;
            }

            if ($items !== []) {
                $this->prescriptions->create($visit->patient, $items, $doctor, $visit);
                $visit->update(['status' => VisitStatus::AwaitingPharmacy, 'care_type' => CareType::Outpatient]);
            } else {
                $visit->update(['status' => VisitStatus::Completed, 'completed_at' => now()]);
            }
        });

        $this->audit->record('consultation.completed', "Completed a consultation for {$visit->patient->full_name}.", $visit);
    }
}
