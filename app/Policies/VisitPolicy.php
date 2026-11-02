<?php

namespace App\Policies;

use App\Enums\PatientStatus;
use App\Enums\Permission;
use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use App\Services\PassportAccessService;

/**
 * Decides who may record each stage of a visit. A worker needs the visits
 * permission, the position that owns the stage, an open passport, and the next
 * stage in the workflow (or an existing stage to correct). This is checked on
 * the server for every save, so a stage cannot be recorded by posting directly.
 */
class VisitPolicy
{
    public function __construct(private readonly PassportAccessService $access)
    {
    }

    public function recordStage(User $user, Visit $visit, string $type): bool
    {
        $stage = VisitStageType::tryFrom($type);

        return $stage !== null
            && $stage !== VisitStageType::Dispensing
            && $visit->isStageAvailable($stage)
            && $this->allowed($user, $visit, Permission::ManageVisits, $this->positionsFor($type));
    }

    public function dispense(User $user, Visit $visit): bool
    {
        return $visit->status === VisitStatus::AwaitingDispense
            && $visit->stages()->where('type', VisitStageType::Consultation->value)->exists()
            && $visit->medications()->exists()
            && $this->allowed($user, $visit, Permission::DispenseMedications, $this->positionsFor('dispensing'));
    }

    /**
     * @param  array<int, string>  $positions
     */
    private function allowed(User $user, Visit $visit, Permission $permission, array $positions): bool
    {
        if (! $user->can($permission->value)) {
            return false;
        }

        if ($visit->isLocked()) {
            return false;
        }

        if (! $user->hasPosition($positions)) {
            return false;
        }

        if (! $user->facility_id || $visit->facility_id !== $user->facility_id) {
            return false;
        }

        $patient = $visit->patient;

        return $patient !== null
            && $patient->status === PatientStatus::Active
            && $this->access->hasOpen($user, $patient);
    }

    /**
     * @return array<int, string>
     */
    private function positionsFor(string $type): array
    {
        return (array) config("health_passport.stage_positions.{$type}", []);
    }

}
