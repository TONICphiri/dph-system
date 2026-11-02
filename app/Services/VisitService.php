<?php

namespace App\Services;

use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use App\Exceptions\WorkflowException;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitMedication;
use App\Models\VisitObservation;
use Illuminate\Support\Facades\DB;

/**
 * Writes the stages of a staged visit. The caller has already checked, through
 * the VisitPolicy, that the worker holds the stage's position, has an open
 * passport and is recording the next stage. A locked visit cannot be changed.
 */
class VisitService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * The visit a worker is adding to: the patient's open visit, or a new,
     * unsaved one when no visit has been started yet.
     */
    public function openOrDraft(Patient $patient, User $worker): Visit
    {
        return DB::transaction(function () use ($patient, $worker) {
            $patient = Patient::query()->whereKey($patient->id)->lockForUpdate()->firstOrFail();

            $open = $patient->visits()->open()->latest('started_at')->first();

            if ($open) {
                return $open;
            }

            $latest = $patient->visits()->latest('started_at')->first();

            if ($latest?->isLocked()) {
                throw new WorkflowException('This holder has a completed visit. Start a new visit explicitly before recording another stage.');
            }

            return new Visit([
                'patient_id' => $patient->id,
                'facility_id' => $worker->facility_id,
                'status' => VisitStatus::InProgress,
            ]);
        });
    }

    public function startAfterCompleted(Patient $patient, User $worker): Visit
    {
        return DB::transaction(function () use ($patient, $worker) {
            $patient = Patient::query()->whereKey($patient->id)->lockForUpdate()->firstOrFail();

            if ($patient->visits()->open()->exists()) {
                throw new WorkflowException('A visit is already in progress for this holder.');
            }

            $latest = $patient->visits()->latest('started_at')->first();

            if (! $latest?->isLocked()) {
                throw new WorkflowException('A new visit can only be started after a completed visit.');
            }

            $visit = Visit::create([
                'patient_id' => $patient->id,
                'facility_id' => $worker->facility_id,
                'status' => VisitStatus::InProgress,
                'started_by' => $worker->id,
                'started_at' => now(),
            ]);

            $this->audit->record('visit.started', "Started a new visit for {$patient->full_name}.", $visit);

            return $visit;
        });
    }

    /**
     * @param  array<string, mixed>  $data  the validated stage fields
     */
    public function saveStage(Patient $patient, Visit $visit, VisitStageType $type, array $data, User $worker): Visit
    {
        return DB::transaction(function () use ($patient, $visit, $type, $data, $worker) {
            if ($type === VisitStageType::Dispensing) {
                throw new WorkflowException('Record dispensing by handing over the prescribed medicines.');
            }

            $patient = Patient::query()->whereKey($patient->id)->lockForUpdate()->firstOrFail();

            if (! $visit->exists) {
                if ($type !== VisitStageType::Reception) {
                    throw new WorkflowException('Record reception before moving to the next visit stage.');
                }

                $visit = $patient->visits()->open()->lockForUpdate()->latest('started_at')->first()
                    ?? Visit::create([
                        'patient_id' => $patient->id,
                        'facility_id' => $worker->facility_id,
                        'status' => VisitStatus::InProgress,
                        'started_by' => $worker->id,
                        'started_at' => now(),
                    ]);
            } else {
                $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);
            }

            if ($visit->patient_id !== $patient->id || $visit->facility_id !== $worker->facility_id) {
                throw new WorkflowException('This visit cannot be continued from your facility.');
            }

            if ($visit->isLocked()) {
                throw new WorkflowException('This visit is complete and can no longer be changed.');
            }

            $this->assertStageOrder($visit, $type);

            $this->writeStage($visit, $type, $data, $worker);
            $this->recordVitals($visit, $data, $worker);

            if ($type === VisitStageType::Reception && array_key_exists('reason', $data)) {
                $visit->update(['reason' => $data['reason']]);
            }

            if ($type === VisitStageType::Consultation) {
                $this->recordConsultation($visit, $data, $worker);
            }

            $this->audit->record(
                "visit.{$type->value}.recorded",
                "Recorded the {$type->label()} stage of a visit for {$patient->full_name}.",
                $visit,
            );

            return $visit->refresh();
        });
    }

    /**
     * Marks the chosen medicines as handed over. The visit completes and locks
     * once every prescribed medicine has been dispensed.
     *
     * @param  array<int, int|string>  $ids
     */
    public function dispense(Visit $visit, array $ids, User $worker): Visit
    {
        return DB::transaction(function () use ($visit, $ids, $worker) {
            $visit = Visit::query()->lockForUpdate()->findOrFail($visit->id);

            if ($visit->isLocked()) {
                throw new WorkflowException('This visit is complete and can no longer be changed.');
            }

            if (
                $visit->status !== VisitStatus::AwaitingDispense
                || ! $visit->stages()->where('type', VisitStageType::Consultation->value)->exists()
                || ! $visit->medications()->exists()
            ) {
                throw new WorkflowException('Dispensing is available only after consultation when medicines have been prescribed.');
            }

            $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();
            $pendingIds = $visit->medications()
                ->whereIn('id', $ids)
                ->where('dispensed', false)
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($pendingIds->isEmpty() || $pendingIds->count() !== $ids->count()) {
                throw new WorkflowException('Select one or more medicines that are still waiting to be handed over.');
            }

            $visit->medications()->whereIn('id', $pendingIds)->where('dispensed', false)->update([
                'dispensed' => true,
                'dispensed_by' => $worker->id,
                'dispensed_at' => now(),
            ]);

            $total = $visit->medications()->count();
            $remaining = $visit->medications()->where('dispensed', false)->count();
            $handed = $total - $remaining;

            $visit->stages()->updateOrCreate(
                ['type' => VisitStageType::Dispensing],
                [
                    'summary' => "Handed over {$handed} of {$total} medicines.",
                    'recorded_at' => now(),
                    'recorded_by' => $worker->id,
                    'recorded_as_position' => $worker->position?->value,
                ],
            );

            if ($remaining === 0 && $total > 0) {
                $visit->update([
                    'status' => VisitStatus::Completed,
                    'completed_by' => $worker->id,
                    'completed_at' => now(),
                    'locked_at' => now(),
                ]);
            }

            $this->audit->record('visit.dispensed', "Handed over {$handed} of {$total} medicines for {$visit->patient->full_name}.", $visit);

            return $visit->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeStage(Visit $visit, VisitStageType $type, array $data, User $worker): void
    {
        $existing = $visit->stages()->where('type', $type->value)->first();

        $attributes = [
            'type' => $type,
            'summary' => $this->summaryFor($type, $data),
            'payload' => $this->payloadFor($type, $data),
        ];

        if ($existing) {
            $existing->update([
                ...$attributes,
                'edited_by' => $worker->id,
                'edited_at' => now(),
            ]);

            return;
        }

        $visit->stages()->create([
            ...$attributes,
            'recorded_by' => $worker->id,
            'recorded_as_position' => $worker->position?->value,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Vitals are added, never overwritten. A reading equal to the newest one
     * for the same sign is not stored again.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordVitals(Visit $visit, array $data, User $worker): void
    {
        foreach ($this->vitalsFrom($data) as $type => $value) {
            $latest = $visit->latestObservation($type);

            if ($latest && (string) $latest->value === (string) $value) {
                continue;
            }

            VisitObservation::create([
                'visit_id' => $visit->id,
                'type' => $type,
                'value' => $value,
                'recorded_by' => $worker->id,
                'recorded_as_position' => $worker->position?->value,
                'recorded_at' => now(),
            ]);

            $visit->unsetRelation('observations');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function recordConsultation(Visit $visit, array $data, User $worker): void
    {
        if ($visit->medications()->where('dispensed', true)->exists()) {
            throw new WorkflowException('Medicines have already been handed over, so the consultation can no longer be changed.');
        }

        $visit->update(['follow_up_on' => $data['follow_up_on'] ?? null]);

        $visit->medications()->delete();

        foreach ($this->medicationRows($data) as $row) {
            VisitMedication::create([
                'visit_id' => $visit->id,
                ...$row,
                'prescribed_by' => $worker->id,
                'prescribed_at' => now(),
            ]);
        }

        $this->syncFollowUpReminder($visit, $worker);

        $hasMedicines = $visit->medications()->exists();

        $visit->update($hasMedicines
            ? ['status' => VisitStatus::AwaitingDispense]
            : [
                'status' => VisitStatus::Completed,
                'completed_by' => $worker->id,
                'completed_at' => now(),
                'locked_at' => now(),
            ]);
    }

    private function assertStageOrder(Visit $visit, VisitStageType $type): void
    {
        $prerequisite = $visit->missingStagePrerequisite($type);

        if ($prerequisite) {
            throw new WorkflowException("Record the {$prerequisite->label()} stage before moving to {$type->label()}.");
        }
    }

    private function syncFollowUpReminder(Visit $visit, User $worker): void
    {
        Reminder::query()
            ->where('patient_id', $visit->patient_id)
            ->where('source_type', $visit->getMorphClass())
            ->where('source_id', $visit->id)
            ->delete();

        if (! $visit->follow_up_on) {
            return;
        }

        Reminder::create([
            'patient_id' => $visit->patient_id,
            'category' => ReminderCategory::FollowUp,
            'title' => 'Follow up visit',
            'message' => "Please return to a health facility on {$visit->follow_up_on->format('j F Y')} for your follow up.",
            'due_on' => max(today(), $visit->follow_up_on->copy()->subDay())->toDateString(),
            'status' => ReminderStatus::Active,
            'source_type' => $visit->getMorphClass(),
            'source_id' => $visit->id,
            'created_by' => $worker->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function summaryFor(VisitStageType $type, array $data): ?string
    {
        return match ($type) {
            VisitStageType::Reception => $data['reason'] ?? null,
            VisitStageType::Triage => $data['symptoms'] ?? null,
            VisitStageType::Consultation => $data['diagnosis'] ?? null,
            VisitStageType::Dispensing => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function payloadFor(VisitStageType $type, array $data): ?array
    {
        return match ($type) {
            VisitStageType::Reception => null,
            VisitStageType::Triage => [
                'symptoms' => $data['symptoms'] ?? null,
                'tests' => $this->testRows($data),
            ],
            VisitStageType::Consultation => [
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment' => $data['treatment_summary'] ?? null,
            ],
            VisitStageType::Dispensing => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{name: string, result: ?string}>
     */
    private function testRows(array $data): array
    {
        return collect($data['tests'] ?? [])
            ->filter(fn (array $row) => filled($row['name'] ?? null) || filled($row['result'] ?? null))
            ->map(fn (array $row) => ['name' => (string) ($row['name'] ?? ''), 'result' => $row['result'] ?? null])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function medicationRows(array $data): array
    {
        return collect($data['medications'] ?? [])
            ->filter(fn (array $row) => filled($row['name'] ?? null))
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'dosage' => $row['dosage'] ?? '',
                'frequency' => $row['frequency'] ?? '',
                'duration_days' => $row['duration_days'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Turns the vital sign fields of a form into observation rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function vitalsFrom(array $data): array
    {
        $vitals = [];

        foreach (['temperature', 'weight', 'height', 'pulse_rate', 'oxygen_saturation'] as $field) {
            if (filled($data[$field] ?? null)) {
                $vitals[match ($field) {
                    'pulse_rate' => 'pulse',
                    'oxygen_saturation' => 'oxygen',
                    default => $field,
                }] = (string) $data[$field];
            }
        }

        if (filled($data['systolic_pressure'] ?? null) && filled($data['diastolic_pressure'] ?? null)) {
            $vitals['bp'] = $data['systolic_pressure'].'/'.$data['diastolic_pressure'];
        }

        return $vitals;
    }
}
