<?php

namespace App\Enums;

/**
 * Stage of an inpatient admission.
 */
enum AdmissionStatus: string
{
    use HasOptions;

    case AwaitingBed = 'awaiting_bed';
    case Admitted = 'admitted';
    case Discharged = 'discharged';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingBed => 'Awaiting bed',
            self::Admitted => 'Admitted',
            self::Discharged => 'Discharged',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AwaitingBed => 'warning',
            self::Admitted => 'info',
            self::Discharged => 'success',
        };
    }
}
