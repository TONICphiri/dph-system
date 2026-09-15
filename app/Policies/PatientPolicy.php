<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * Own-file rule for medical details.
 * - Patient-role accounts open ONLY the clinical file linked to them.
 * - Staff keep the existing permission-based access (consent included).
 */
class PatientPolicy
{
    public function view(User $user, Patient $patient): bool
    {
        if ($user->hasRole('patient')) {
            return $user->ownsPatient($patient);
        }

        return $user->can('view_patient');
    }
}
