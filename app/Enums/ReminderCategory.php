<?php

namespace App\Enums;

/**
 * What a patient reminder is about.
 */
enum ReminderCategory: string
{
    use HasOptions;

    case Vaccination = 'vaccination';
    case Medication = 'medication';
    case Appointment = 'appointment';

    public function label(): string
    {
        return match ($this) {
            self::Vaccination => 'Vaccination',
            self::Medication => 'Medication',
            self::Appointment => 'Appointment',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Vaccination => 'info',
            self::Medication => 'warning',
            self::Appointment => 'neutral',
        };
    }
}
