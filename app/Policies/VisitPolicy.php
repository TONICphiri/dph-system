<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Visit;

class VisitPolicy
{
    use ChecksFacility;

    public function recordVitals(User $user, Visit $visit): bool
    {
        return $user->can(Permission::RecordVitals->value) && $this->sameFacility($user, $visit);
    }

    public function consult(User $user, Visit $visit): bool
    {
        return $user->can(Permission::ConductConsultations->value) && $this->sameFacility($user, $visit);
    }

    public function cancel(User $user, Visit $visit): bool
    {
        return $user->can(Permission::CheckInPatients->value) && $this->sameFacility($user, $visit);
    }

    /**
     * The electronic visit report: full record staff, or the patient.
     */
    public function viewReport(User $user, Visit $visit): bool
    {
        return $user->can(Permission::ViewFullMedicalRecord->value) || $user->ownsPatientRecord($visit->patient);
    }
}
