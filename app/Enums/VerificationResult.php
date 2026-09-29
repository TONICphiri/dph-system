<?php

namespace App\Enums;

enum VerificationResult: string
{
    use HasOptions;

    case Valid = 'valid';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Invalid = 'invalid';
    case NotFound = 'not_found';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
            self::Invalid => 'Invalid',
            self::NotFound => 'Not Found',
        };
    }
}
