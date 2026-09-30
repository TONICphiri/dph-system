<?php

namespace App\Console\Commands;

use App\Enums\CredentialStatus;
use App\Models\Credential;
use App\Services\DhpAuditLogger;
use Illuminate\Console\Command;

/**
 * Marks elapsed active credentials as expired. Idempotent: only
 * status=active rows with a past expiry_date are touched, so reruns
 * change nothing and write no duplicate audits. Revoked and superseded
 * records are never altered. Verification also checks dates directly,
 * so results stay correct even if this job has not run yet.
 */
class MarkCredentialsExpired extends Command
{
    protected $signature = 'credentials:mark-expired';

    protected $description = 'Mark active credentials with a past expiry date as expired.';

    public function handle(): int
    {
        $expiredCount = 0;

        Credential::query()
            ->where('status', CredentialStatus::Active)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', today())
            ->chunkById(200, function ($credentials) use (&$expiredCount) {
                foreach ($credentials as $credential) {
                    $credential->update(['status' => CredentialStatus::Expired]);
                    $expiredCount++;

                    DhpAuditLogger::log(
                        user: null,
                        action: 'credential_expired',
                        entityType: 'credential',
                        entityId: $credential->id,
                        details: [
                            'credential_type' => $credential->type->value,
                            'has_expiry' => true,
                        ],
                    );
                }
            });

        DhpAuditLogger::log(
            user: null,
            action: 'credentials_expiry_job_completed',
            entityType: 'job',
            entityId: null,
            details: ['expired_count' => $expiredCount],
        );

        $this->info("Marked {$expiredCount} credential(s) as expired.");

        return self::SUCCESS;
    }
}
