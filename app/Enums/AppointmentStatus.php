<?php

namespace App\Enums;

/**
 * Approval status of an appointment.
 */
enum AppointmentStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Declined => 'danger',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
