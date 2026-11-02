<?php

namespace App\Enums;

/**
 * Whether a passport holder's identity has been fully confirmed. A child
 * registered without the mother present starts as provisional until the
 * mother is linked and the date of birth is confirmed.
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
