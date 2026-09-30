<?php

namespace App\Console\Commands;

use App\Enums\CredentialStatus;
use App\Models\Credential;
use App\Models\NotificationDelivery;
use App\Services\DhpAuditLogger;
use App\Services\DhpNotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * Notify citizens whose active credential expires in exactly 7 days.
 * Idempotent via notification_deliveries: reruns queue nothing twice.
 * Citizens without a linked email user are counted, never failed.
 */
class NotifyExpiringCredentials extends Command
{
    protected $signature = 'credentials:notify-expiring';

    protected $description = 'Queue expiring-soon notifications for credentials expiring in 7 days.';

    public function handle(): int
    {
        $queuedCount = 0;
        $skippedNoEmail = 0;

        Credential::query()
            ->where('status', CredentialStatus::Active)
            ->whereDate('expiry_date', today()->addDays(7))
            ->with(['citizen.user'])
            ->chunkById(200, function ($credentials) use (&$queuedCount, &$skippedNoEmail) {
                foreach ($credentials as $credential) {
                    // Defensive: never notify elapsed, revoked or replaced rows.
                    if ($credential->effective_status !== CredentialStatus::Active) {
                        continue;
                    }

                    $email = $credential->citizen->user?->email;

                    if (DhpNotificationService::eligibleEmail($email) === null) {
                        $skippedNoEmail++;

                        continue;
                    }

                    try {
                        $delivery = NotificationDelivery::query()->firstOrCreate(
                            [
                                'notification_type' => 'credential_expiring_soon',
                                'related_type' => 'credential',
                                'related_id' => $credential->id,
                            ],
                            [
                                'notifiable_type' => get_class($credential->citizen->user),
                                'notifiable_id' => $credential->citizen->user->id,
                                'status' => 'queued',
                                'queued_at' => now(),
                            ]
                        );
                    } catch (QueryException) {
                        // Lost a race with another run: already tracked, skip.
                        continue;
                    }

                    if (! $delivery->wasRecentlyCreated) {
                        continue;
                    }

                    if (DhpNotificationService::queueExpiringSoon($credential)) {
                        $queuedCount++;

                        DhpAuditLogger::log(
                            user: null,
                            action: 'credential_expiry_notification_queued',
                            entityType: 'credential',
                            entityId: $credential->id,
                            details: ['notification' => 'credential_expiring_soon', 'queued' => true],
                        );
                    } else {
                        $delivery->update(['status' => 'failed', 'failed_at' => now()]);
                    }
                }
            });

        DhpAuditLogger::log(
            user: null,
            action: 'credentials_expiry_notification_job_completed',
            entityType: 'job',
            entityId: null,
            details: ['queued_count' => $queuedCount, 'skipped_no_email_count' => $skippedNoEmail],
        );

        $this->info("Queued {$queuedCount} expiring-soon notification(s), skipped {$skippedNoEmail} without email.");

        return self::SUCCESS;
    }
}
