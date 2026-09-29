<?php

namespace App\Enums;

/**
 * Passport roles. Replaces the legacy hospital roles
 * (system_admin, facility_admin, health_worker, patient).
 */
enum DhpRole: string
{
    use HasOptions;

    case Citizen = 'citizen';
    case Issuer = 'issuer';
    case Verifier = 'verifier';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Citizen => 'Citizen',
            self::Issuer => 'Issuer',
            self::Verifier => 'Verifier',
            self::Admin => 'Administrator',
        };
    }
}
