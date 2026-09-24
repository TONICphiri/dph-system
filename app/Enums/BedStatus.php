<?php

namespace App\Enums;

/**
 * Occupancy status of a hospital bed.
 */
enum BedStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Occupied => 'Occupied',
            self::Maintenance => 'Under maintenance',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Occupied => 'warning',
            self::Maintenance => 'neutral',
        };
    }
}
