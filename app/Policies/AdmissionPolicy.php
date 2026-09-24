<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Admission;
use App\Models\User;

class AdmissionPolicy
{
    use ChecksFacility;

    public function view(User $user, Admission $admission): bool
    {
        return $user->can(Permission::ViewWardStatus->value) && $this->sameFacility($user, $admission);
    }

    public function allocateBed(User $user, Admission $admission): bool
    {
        return $user->can(Permission::AllocateBeds->value) && $this->sameFacility($user, $admission);
    }

    public function writeNote(User $user, Admission $admission): bool
    {
        return $user->can(Permission::WriteProgressNotes->value) && $this->sameFacility($user, $admission);
    }

    public function recordVitals(User $user, Admission $admission): bool
    {
        return $user->can(Permission::RecordVitals->value) && $this->sameFacility($user, $admission);
    }

    public function recordMedication(User $user, Admission $admission): bool
    {
        return $user->can(Permission::RecordMedicationAdministration->value) && $this->sameFacility($user, $admission);
    }

    public function prescribe(User $user, Admission $admission): bool
    {
        return $user->can(Permission::PrescribeMedication->value) && $this->sameFacility($user, $admission);
    }

    public function discharge(User $user, Admission $admission): bool
    {
        return $user->can(Permission::DischargePatients->value) && $this->sameFacility($user, $admission);
    }

    /**
     * The full inpatient report: full record staff, or the patient.
     */
    public function viewReport(User $user, Admission $admission): bool
    {
        return ($user->can(Permission::ViewFullMedicalRecord->value))
            || $user->ownsPatientRecord($admission->patient);
    }
}
