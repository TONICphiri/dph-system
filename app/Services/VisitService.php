<?php

namespace App\Services;

use App\Enums\PatientStatus;
use App\Enums\VisitStatus;
use App\Exceptions\WorkflowException;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Vital;

/**
 * Handles check in and vital signs, the first two steps of every visit.
 */
class VisitService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function checkIn(Patient $patient, string $reason, User $clerk): Visit
    {
        if ($patient->status !== PatientStatus::Active) {
            throw new WorkflowException('Only active patients can be checked in.');
        }

        $alreadyOpen = Visit::query()
            ->where('patient_id', $patient->id)
            ->where('facility_id', $clerk->facility_id)
            ->open()
            ->exists();

        if ($alreadyOpen) {
            throw new WorkflowException("{$patient->full_name} already has an open visit at this facility.");
        }

        $visit = Visit::create([
            'patient_id' => $patient->id,
            'facility_id' => $clerk->facility_id,
            'checked_in_by' => $clerk->id,
            'reason_for_visit' => $reason,
            'status' => VisitStatus::WaitingForVitals,
            'checked_in_at' => now(),
        ]);

        $this->audit->record('visit.checked-in', "Checked in {$patient->full_name} for: {$reason}.", $visit);

        return $visit;
    }

    /**
     * @param  array<string, mixed>  $measurements
     */
    public function recordVitals(Visit $visit, array $measurements, User $nurse): Vital
    {
        if (! $visit->isOpen()) {
            throw new WorkflowException('Vital signs can only be recorded for an open visit.');
        }

        $vital = $visit->vitals()->create([
            ...$measurements,
            'patient_id' => $visit->patient_id,
            'recorded_by' => $nurse->id,
            'recorded_at' => now(),
        ]);

        if ($visit->status === VisitStatus::WaitingForVitals) {
            $visit->update(['status' => VisitStatus::WaitingForDoctor]);
        }

        $this->audit->record('vitals.recorded', "Recorded vital signs for {$visit->patient->full_name}.", $vital);

        return $vital;
    }

    public function cancel(Visit $visit): void
    {
        if (! in_array($visit->status, [VisitStatus::WaitingForVitals, VisitStatus::WaitingForDoctor], true)) {
            throw new WorkflowException('A visit can only be cancelled before the consultation starts.');
        }

        $visit->update(['status' => VisitStatus::Cancelled, 'completed_at' => now()]);
        $this->audit->record('visit.cancelled', "Cancelled the visit for {$visit->patient->full_name}.", $visit);
    }
}
