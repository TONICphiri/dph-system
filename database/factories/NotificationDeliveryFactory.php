<?php

namespace Database\Factories;

use App\Models\NotificationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    public function definition(): array
    {
        return [
            'notification_type' => 'credential_expiring_soon',
            'related_type' => 'credential',
            'related_id' => \App\Models\Credential::factory(),
            'status' => 'queued',
            'queued_at' => now(),
        ];
    }
}
