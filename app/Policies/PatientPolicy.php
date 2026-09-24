<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;

/**
 * Patient records are national: staff at any facility can find a patient.
 * What they can see inside the record depends on their role.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewPatientDemographics->value);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ViewPatientDemographics->value) || $user->ownsPatientRecord($patient);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RegisterPatients->value);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can(Permission::EditPatientDemographics->value);
    }

    public function viewBasicHistory(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ViewBasicHistory->value) || $user->ownsPatientRecord($patient);
    }

    public function viewFullRecord(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ViewFullMedicalRecord->value) || $user->ownsPatientRecord($patient);
    }

    public function viewVaccinations(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ViewVaccinations->value) || $user->ownsPatientRecord($patient);
    }

    public function printCard(User $user, Patient $patient): bool
    {
        return $user->can(Permission::RegisterPatients->value) || $user->ownsPatientRecord($patient);
    }
}
