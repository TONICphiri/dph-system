<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\Credential;
use App\Models\User;
use App\Notifications\Dhp\CitizenAccountCreatedNotification;
use App\Notifications\Dhp\CredentialExpiringSoonNotification;
use App\Notifications\Dhp\CredentialIssuedNotification;
use App\Notifications\Dhp\CredentialRevokedOrReplacedNotification;
use App\Notifications\Dhp\StaffAccountCreatedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * Single place that decides whether a passport email may be queued.
 * Missing or malformed recipient email silently skips (never an error),
 * so citizens without email are never blocked. Reset tokens travel only
 * inside the one-time emailed URL, never in logs or audit metadata.
 */
class DhpNotificationService
{
    public static function eligibleEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * One-time set-password URL via Laravel's password broker.
     */
    public static function resetUrl(User $user): string
    {
        $token = Password::createToken($user);

        return route('password.reset', ['token' => $token, 'email' => $user->email]);
    }

    public static function portalUrl(): string
    {
        return route('dhp.citizen.dashboard');
    }

    public static function queueCitizenAccountCreated(User $user, Citizen $citizen, ?User $actor = null): bool
    {
        if (self::eligibleEmail($user->email) === null) {
            return false;
        }

        return self::dispatch(
            $user,
            new CitizenAccountCreatedNotification($citizen->first_name, self::resetUrl($user)),
            'citizen_account_created',
            ['notification' => 'citizen_account_created', 'queued' => true],
            'citizen_account_activation_notification_queued',
            $actor,
        );
    }

    public static function queueStaffAccountCreated(User $user, ?User $actor = null): bool
    {
        if (self::eligibleEmail($user->email) === null) {
            return false;
        }

        return self::dispatch(
            $user,
            new StaffAccountCreatedNotification($user->first_name ?? $user->name, $user->role->label(), self::resetUrl($user)),
            'staff_account_created',
            ['notification' => 'staff_account_created', 'queued' => true, 'role' => $user->role->value],
            'staff_account_activation_notification_queued',
            $actor,
        );
    }

    public static function queueCredentialIssued(Credential $credential, ?User $actor = null): bool
    {
        $recipient = $credential->citizen->user ?? null;

        if (! $recipient || self::eligibleEmail($recipient->email) === null) {
            return false;
        }

        return self::dispatch(
            $recipient,
            new CredentialIssuedNotification(
                $credential->citizen->first_name,
                $credential->type->label(),
                $credential->credential_number,
                $credential->issue_date->format('j M Y'),
                $credential->expiry_date?->format('j M Y'),
                self::portalUrl(),
            ),
            'credential_issued_notification',
            ['notification' => 'credential_issued', 'queued' => true],
            null,
            $actor,
        );
    }

    public static function queueCredentialRevoked(Credential $credential, string $reasonCategory, bool $replaced, ?User $actor = null): bool
    {
        $recipient = $credential->citizen->user ?? null;

        if (! $recipient || self::eligibleEmail($recipient->email) === null) {
            return false;
        }

        return self::dispatch(
            $recipient,
            new CredentialRevokedOrReplacedNotification(
                $credential->citizen->first_name,
                $credential->credential_number,
                $replaced ? 'replaced' : $credential->status->label(),
                $reasonCategory,
                self::portalUrl(),
            ),
            'credential_revoked_notification',
            ['notification' => 'credential_revoked_or_replaced', 'queued' => true],
            null,
            $actor,
        );
    }

    public static function queueExpiringSoon(Credential $credential): bool
    {
        $recipient = $credential->citizen->user ?? null;

        if (! $recipient || self::eligibleEmail($recipient->email) === null) {
            return false;
        }

        return self::dispatch(
            $recipient,
            new CredentialExpiringSoonNotification(
                $credential->citizen->first_name,
                $credential->type->label(),
                $credential->credential_number,
                $credential->expiry_date->format('j M Y'),
                self::portalUrl(),
            ),
            'credential_expiring_soon',
            ['notification' => 'credential_expiring_soon', 'queued' => true],
            null,
            null,
        );
    }

    /**
     * Queue the notification; on synchronous dispatch failure write only
     * a safe failure audit and report false. Returns true when queued.
     */
    private static function dispatch(
        User $recipient,
        object $notification,
        string $logName,
        array $auditDetails,
        ?string $auditAction,
        ?User $actor,
    ): bool {
        try {
            $recipient->notify($notification);
        } catch (Throwable $exception) {
            Log::warning("DHP notification {$logName} could not be queued.", ['error' => $exception->getMessage()]);

            DhpAuditLogger::log(
                user: $actor,
                action: 'notification_queue_failed',
                entityType: 'notification',
                entityId: null,
                details: ['notification' => $auditDetails['notification'] ?? $logName],
            );

            return false;
        }

        if ($auditAction !== null) {
            DhpAuditLogger::log(
                user: $actor,
                action: $auditAction,
                entityType: $auditDetails['entity_type'] ?? 'notification',
                entityId: $auditDetails['entity_id'] ?? null,
                details: $auditDetails,
            );
        }

        return true;
    }
}
