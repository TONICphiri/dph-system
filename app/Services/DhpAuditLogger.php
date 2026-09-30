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
     * Two precise exceptions keep required safe metadata:
     * - exact key "result" with a verification outcome value
     *   (valid/expired/revoked/superseded/invalid/not_found);
     * - "has_*" boolean presence flags (e.g. has_national_id: true).
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
        'test_result',
        'result_date',
        'result_value',
        'lab_result',
        'diagnos',
        'medical',
        'qr_token',
        'qrtoken',
        'token',
        'session',
        'remember',
    ];

    /**
     * @var array<int, string>
     */
    private const OUTCOME_VALUES = ['valid', 'expired', 'revoked', 'superseded', 'invalid', 'not_found'];

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

            // Verification outcome only: any other "result" value is clinical.
            if ($name === 'result') {
                if (is_string($value) && in_array(strtolower($value), self::OUTCOME_VALUES, true)) {
                    $clean[$key] = $value;
                }

                continue;
            }

            // Presence flags only: a non-boolean has_* value may carry identity.
            if (str_starts_with($name, 'has_')) {
                if (is_bool($value)) {
                    $clean[$key] = $value;
                }

                continue;
            }

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
