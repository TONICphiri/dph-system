<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Minimal audit writer for Digital Health Passport events.
 *
 * Writes the spec-shaped columns (entity_type, entity_id, details JSON) plus
 * a short human description so the legacy audit viewer keeps working.
 * Sensitive values are stripped from details and NEVER stored:
 * passwords, PINs, National ID, date of birth, results, medical detail,
 * QR tokens and session/reset tokens.
 */
class DhpAuditLogger
{
    /**
     * Keys removed from details at any nesting level (case-insensitive,
     * matched as substrings so e.g. "nationalId" and "NATIONAL_ID" also go).
     *
     * @var array<int, string>
     */
    public const SENSITIVE_KEYS = [
        'password',
        'pin',
        'national_id',
        'nationalid',
        'date_of_birth',
        'dob',
        'birth',
        'result',
        'diagnos',
        'medical',
        'qr_token',
        'qrtoken',
        'token',
        'session',
        'remember',
    ];

    public static function log(
        ?User $user,
        string $action,
        ?string $entityType,
        mixed $entityId = null,
        array $details = [],
        ?string $ipAddress = null,
    ): void {
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'facility_id' => $user?->facility_id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId !== null ? (int) $entityId : null,
                'description' => $action,
                'details' => self::scrub($details),
                'ip_address' => $ipAddress ?? request()?->ip(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('DHP audit entry could not be saved.', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Remove sensitive keys recursively; keeps scalars only.
     */
    public static function scrub(array $details): array
    {
        $clean = [];

        foreach ($details as $key => $value) {
            $name = strtolower((string) $key);

            foreach (self::SENSITIVE_KEYS as $banned) {
                if (str_contains($name, $banned)) {
                    continue 2;
                }
            }

            $clean[$key] = is_array($value) ? self::scrub($value) : (is_scalar($value) || $value === null ? $value : '[removed]');
        }

        return $clean;
    }
}
