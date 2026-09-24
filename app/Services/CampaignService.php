<?php

namespace App\Services;

use App\Enums\CampaignAudience;
use App\Enums\RoleName;
use App\Enums\Sex;
use App\Enums\UserStatus;
use App\Exceptions\WorkflowException;
use App\Models\HealthCampaign;
use App\Models\User;
use App\Notifications\HealthCampaignNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

/**
 * Publishes health campaign messages (for example dental, nutrition, maternal
 * health or cancer screening) to patient portal accounts.
 */
class CampaignService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function publish(HealthCampaign $campaign): int
    {
        if ($campaign->isPublished()) {
            throw new WorkflowException('This campaign has already been published.');
        }

        $count = 0;

        $this->recipients($campaign)->chunkById(500, function ($users) use ($campaign, &$count) {
            Notification::send($users, new HealthCampaignNotification($campaign));
            $count += $users->count();
        });

        $campaign->update(['published_at' => now(), 'recipients_count' => $count]);
        $this->audit->record('campaign.published', "Published \"{$campaign->title}\" to {$count} patients.", $campaign);

        return $count;
    }

    public function recipients(HealthCampaign $campaign): Builder
    {
        $query = User::query()
            ->role(RoleName::Patient->value)
            ->where('status', UserStatus::Active)
            ->whereNotNull('patient_id');

        if ($campaign->facility_id) {
            $query->whereHas('patient', fn (Builder $patient) => $patient->where('registered_facility_id', $campaign->facility_id));
        }

        return match ($campaign->audience) {
            CampaignAudience::FemalePatients => $query->whereHas('patient', fn (Builder $patient) => $patient->where('sex', Sex::Female)),
            CampaignAudience::ParentsOfChildren => $query->whereHas('patient.children'),
            default => $query,
        };
    }
}
