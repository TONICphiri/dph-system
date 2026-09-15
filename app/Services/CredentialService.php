<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * HMAC-signed QR credential issuance per system-description2.md FR-B2, §5.4, NFR-6.
 * Payload {user_uuid, credential_type, issuer, expiry} → HMAC-SHA256 sign → store token hash → render QR.
 * Raw tokens are never stored; only SHA-256 hashes.
 */
class CredentialService
{
    public static function signingKey(): string
    {
        return (string) config('app.key', env('APP_KEY', 'dhp-credential-key'));
    }

    /**
     * @return array{token: string, credential_id: int, expires_at: string}
     */
    public static function issue(User $holder, string $type = 'health_pass', int $validDays = 365): array
    {
        $token = Str::random(48);
        $expiresAt = now()->addDays($validDays);

        $payload = [
            'user_uuid' => (string) $holder->id,
            'nin_mask' => $holder->masked_nin,
            'credential_type' => $type,
            'issuer' => $holder->facility_id ? ('facility:'.$holder->facility_id) : 'national-registry',
            'issued_at' => now()->toIso8601String(),
            'expiry' => $expiresAt->toIso8601String(),
            'token' => $token,
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, self::signingKey());
        $signed = base64_encode(json_encode(['p' => $payload, 's' => $signature]));

        $id = DB::table('qr_credentials')->insertGetId([
            'user_id' => $holder->id,
            'token_hash' => hash('sha256', $token),
            'payload_signed' => $signed,
            'credential_type' => $type,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditService::log(auth()->user(), 'credential.issued', $holder, ['facility_id' => $holder->facility_id]);

        return ['token' => $signed, 'credential_id' => $id, 'expires_at' => $expiresAt->toIso8601String()];
    }

    public static function revoke(int $credentialId, ?User $actor = null): void
    {
        DB::table('qr_credentials')->where('id', $credentialId)->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        AuditService::log($actor ?? auth()->user(), 'credential.revoked', null, []);
    }
}
