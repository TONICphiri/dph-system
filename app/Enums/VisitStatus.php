<?php

namespace App\Enums;

/**
 * Stage of a visit in the outpatient workflow.
 */
enum VisitStatus: string
{
    use HasOptions;

    case WaitingForVitals = 'waiting_for_vitals';
    case WaitingForDoctor = 'waiting_for_doctor';
    case AwaitingPharmacy = 'awaiting_pharmacy';
    case Admitted = 'admitted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::WaitingForVitals => 'Waiting for vitals',
            self::WaitingForDoctor => 'Waiting for doctor',
            self::AwaitingPharmacy => 'Awaiting pharmacy',
            self::Admitted => 'Admitted',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::WaitingForVitals => 'warning',
            self::WaitingForDoctor => 'info',
            self::AwaitingPharmacy => 'info',
            self::Admitted => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
