<?php

namespace App\Enums;

/**
 * Whether a visit is outpatient or inpatient care.
 */
enum CareType: string
{
    use HasOptions;

    case Outpatient = 'outpatient';
    case Inpatient = 'inpatient';

    public function label(): string
    {
        return match ($this) {
            self::Outpatient => 'Outpatient',
            self::Inpatient => 'Inpatient',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Outpatient => 'info',
            self::Inpatient => 'warning',
        };
    }
}
