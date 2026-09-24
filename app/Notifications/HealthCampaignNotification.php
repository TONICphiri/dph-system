<?php

namespace App\Notifications;

use App\Models\HealthCampaign;

class HealthCampaignNotification extends SystemNotification
{
    public function __construct(private readonly HealthCampaign $campaign)
    {
    }

    protected function title(): string
    {
        return $this->campaign->title;
    }

    protected function message(): string
    {
        return $this->campaign->message;
    }

    protected function category(): string
    {
        return 'campaign';
    }
}
