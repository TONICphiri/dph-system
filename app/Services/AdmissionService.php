<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\CareType;
use App\Enums\FacilityStatus;
use App\Enums\VisitStatus;
use App\Enums\WardGender;
use App\Exceptions\WorkflowException;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

/**
 * Inpatient workflow: admission decision, ward and bed allocation, and
 * discharge with automatic bed release.
 */
class AdmissionService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function admit(Visit $visit, string $reason, ?string $preferredWardType, User $doctor): Admission
    {
        if ($visit->admission()->exists()) {
            throw new WorkflowException('This patient has already been admitted from this visit.');
        }

        $admission = Admission::create([
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'facility_id' => $visit->facility_id,
            'admitted_by' => $doctor->id,
            'status' => AdmissionStatus::AwaitingBed,
            'admission_reason' => $reason,
            'preferred_ward_type' => $preferredWardType,
            'admitted_at' => now(),
        ]);

        $visit->update(['status' => VisitStatus::Admitted, 'care_type' => CareType::Inpatient]);

        $this->audit->record('admission.created', "Admitted {$visit->patient->full_name}. Awaiting bed allocation.", $admission);

        return $admission;
    }

    public function allocateBed(Admission $admission, int $bedId, User $staff): void
    {
        if ($admission->status !== AdmissionStatus::AwaitingBed) {
            throw new WorkflowException('This patient already has a bed or has been discharged.');
        }

        DB::transaction(function () use ($admission, $bedId, $staff) {
            $bed = Bed::query()->with('ward')->lockForUpdate()->find($bedId);

            if (! $bed || $bed->ward->facility_id !== $admission->facility_id) {
                throw new WorkflowException('The selected bed does not belong to this facility.');
            }

            if ($bed->status !== BedStatus::Available) {
                throw new WorkflowException("Bed {$bed->bed_number} in {$bed->ward->name} is no longer available. Please choose another bed.");
            }

            if ($bed->ward->status !== FacilityStatus::Active) {
                throw new WorkflowException("{$bed->ward->name} is closed and cannot take new patients.");
            }

            $this->assertWardAcceptsPatient($bed, $admission);

            $bed->update(['status' => BedStatus::Occupied]);

            $admission->update([
                'ward_id' => $bed->ward_id,
                'bed_id' => $bed->id,
                'allocated_by' => $staff->id,
                'bed_allocated_at' => now(),
                'status' => AdmissionStatus::Admitted,
            ]);
        });

        $admission->refresh();
        $this->audit->record('admission.bed-allocated', "Allocated {$admission->ward->name}, bed {$admission->bed->bed_number} to {$admission->patient->full_name}.", $admission);
    }

    /**
     * @param  array<string, mixed>  $data  Outcome, summary and follow up instructions.
     */
    public function discharge(Admission $admission, array $data, User $doctor): void
    {
        if ($admission->status === AdmissionStatus::Discharged) {
            throw new WorkflowException('This patient has already been discharged.');
        }

        DB::transaction(function () use ($admission, $data, $doctor) {
            if ($admission->bed_id) {
                Bed::query()->whereKey($admission->bed_id)->update(['status' => BedStatus::Available]);
            }

            $admission->update([
                ...$data,
                'status' => AdmissionStatus::Discharged,
                'discharged_by' => $doctor->id,
                'discharged_at' => now(),
            ]);

            $admission->visit->update([
                'status' => VisitStatus::Completed,
                'completed_at' => now(),
            ]);
        });

        $this->audit->record('admission.discharged', "Discharged {$admission->patient->full_name}. The bed has been released.", $admission);
    }

    private function assertWardAcceptsPatient(Bed $bed, Admission $admission): void
    {
        $restriction = $bed->ward->gender_restriction;

        if ($restriction === WardGender::Mixed) {
            return;
        }

        if ($restriction->value !== $admission->patient->sex->value) {
            throw new WorkflowException("{$bed->ward->name} only accepts {$restriction->value} patients.");
        }
    }
}
