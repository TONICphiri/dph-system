<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    use ChecksFacility;

    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->can(Permission::DispenseMedication->value) && $this->sameFacility($user, $prescription);
    }
}
