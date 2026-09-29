<?php

namespace App\Policies;

use App\Enums\DhpRole;
use App\Models\Citizen;
use App\Models\User;

/**
 * Citizen profiles. Uses users.role only; legacy Spatie roles are ignored.
 * A verifier never sees a full citizen profile; verification uses the
 * minimal-data flow (Phase 5). There is no public citizen endpoint.
 */
class CitizenPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [DhpRole::Issuer, DhpRole::Admin], true);
    }

    public function view(User $user, Citizen $citizen): bool
    {
        return match ($user->role) {
            DhpRole::Admin, DhpRole::Issuer => true,
            // Citizens see only the profile linked to their own login.
            DhpRole::Citizen => $citizen->user_id !== null && $citizen->user_id === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [DhpRole::Issuer, DhpRole::Admin], true);
    }

    public function update(User $user, Citizen $citizen): bool
    {
        return $this->view($user, $citizen) && $user->role !== DhpRole::Citizen;
    }
}
