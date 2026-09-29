<?php

namespace App\Enums;

/**
 * The only two verifiable credential types in this prototype.
 */
enum CredentialType: string
{
    use HasOptions;

    case Vaccination = 'vaccination';
    case LabTest = 'lab_test';

    public function label(): string
    {
        return match ($this) {
            self::Vaccination => 'Vaccination',
            self::LabTest => 'Laboratory test',
        };
    }
}
