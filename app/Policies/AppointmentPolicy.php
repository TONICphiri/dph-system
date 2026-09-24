<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    use ChecksFacility;

    /**
     * Facility Administrators decide any request at their facility. Doctors
     * decide requests allocated to them.
     */
    public function decide(User $user, Appointment $appointment): bool
    {
        if (! $user->can(Permission::ApproveAppointments->value) || ! $this->sameFacility($user, $appointment)) {
            return false;
        }

        return $user->isRole(RoleName::FacilityAdmin) || $appointment->doctor_id === $user->id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->ownsPatientRecord($appointment->patient);
    }

    public function review(User $user, Appointment $appointment): bool
    {
        return $user->ownsPatientRecord($appointment->patient);
    }
}
