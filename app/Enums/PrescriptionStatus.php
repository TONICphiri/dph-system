<?php

namespace App\Enums;

/**
 * Pharmacy status of a prescription.
 */
enum PrescriptionStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Dispensed = 'dispensed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Dispensed => 'Dispensed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Dispensed => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
