<?php

namespace App\Enums;

/**
 * Condition of the patient when discharged.
 */
enum DischargeOutcome: string
{
    use HasOptions;

    case Recovered = 'recovered';
    case Improved = 'improved';
    case Referred = 'referred';
    case AgainstMedicalAdvice = 'against_medical_advice';
    case Deceased = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::Recovered => 'Recovered',
            self::Improved => 'Improved',
            self::Referred => 'Referred to another facility',
            self::AgainstMedicalAdvice => 'Left against medical advice',
            self::Deceased => 'Deceased',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Recovered => 'success',
            self::Improved => 'success',
            self::Referred => 'info',
            self::AgainstMedicalAdvice => 'warning',
            self::Deceased => 'danger',
        };
    }
}
