<?php

namespace App\Enums;

/**
 * Which patients a ward accepts.
 */
enum WardGender: string
{
    use HasOptions;

    case Mixed = 'mixed';
    case Female = 'female';
    case Male = 'male';

    public function label(): string
    {
        return match ($this) {
            self::Mixed => 'Mixed',
            self::Female => 'Female only',
            self::Male => 'Male only',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Mixed => 'neutral',
            self::Female => 'neutral',
            self::Male => 'neutral',
        };
    }
}
