<?php

namespace App\Policies;

use App\Enums\DhpRole;
use App\Models\Credential;
use App\Models\User;

/**
 * Verifiable health credentials. Citizens reach only their own records;
 * verifiers never open full credential records (Phase 5 minimal flow).
 * Admins may view for audit but must not alter credential content.
 */
class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [DhpRole::Issuer, DhpRole::Admin], true);
    }

    public function view(User $user, Credential $credential): bool
    {
        return match ($user->role) {
            DhpRole::Admin, DhpRole::Issuer => true,
            DhpRole::Citizen => $this->owns($user, $credential),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === DhpRole::Issuer;
    }

    public function update(User $user, Credential $credential): bool
    {
        return $user->role === DhpRole::Issuer;
    }

    public function revoke(User $user, Credential $credential): bool
    {
        return $user->role === DhpRole::Issuer;
    }

    public function print(User $user, Credential $credential): bool
    {
        return $user->role === DhpRole::Issuer || ($user->role === DhpRole::Citizen && $this->owns($user, $credential));
    }

    /**
     * True when the credential belongs to the citizen linked to this login.
     */
    private function owns(User $user, Credential $credential): bool
    {
        $citizenId = $user->citizenProfile?->id;

        return $citizenId !== null && $credential->citizen_id === $citizenId;
    }
}
