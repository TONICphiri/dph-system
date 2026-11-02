<?php

namespace App\Enums;

/**
 * Whether a passport holder's identity has been fully confirmed. A patient
 * registered in an emergency starts as provisional until the missing identity
 * details are supplied.
 */
enum IdentityStatus: string
{
    use HasOptions;

    case Verified = 'verified';
    case Provisional = 'provisional';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Provisional => 'Provisional',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Verified => 'success',
            self::Provisional => 'warning',
        };
    }
}
