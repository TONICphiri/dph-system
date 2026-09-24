<?php

namespace App\Enums;

/**
 * Which patients receive a health campaign message.
 */
enum CampaignAudience: string
{
    use HasOptions;

    case AllPatients = 'all_patients';
    case FemalePatients = 'female_patients';
    case ParentsOfChildren = 'parents_of_children';

    public function label(): string
    {
        return match ($this) {
            self::AllPatients => 'All patients',
            self::FemalePatients => 'Female patients',
            self::ParentsOfChildren => 'Parents of registered children',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AllPatients => 'neutral',
            self::FemalePatients => 'neutral',
            self::ParentsOfChildren => 'neutral',
        };
    }
}
