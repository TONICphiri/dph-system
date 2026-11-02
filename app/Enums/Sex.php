<?php

namespace App\Enums;

/**
 * Biological sex recorded on the health passport.
 */
enum Sex: string
{
    use HasOptions;

    case Female = 'female';
    case Male = 'male';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Female',
            self::Male => 'Male',
            self::Unknown => 'Unknown',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Female => 'neutral',
            self::Male => 'neutral',
        };
    }
}
