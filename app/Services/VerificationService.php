<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * QR verification per system-description2.md FR-E1..E3, §5.4.
 * Recompute HMAC, check expiry/revocation → return minimal result → write verification_logs.
 * Verifier sees only: holder name, credential type, validity status, expiry, issuing authority.
 */
class VerificationService
{
    /**
     * @return array{valid: bool, status: string, holder_name?: string, credential_type?: string, expires_at?: string, issuer?: string}
     */
    public static function verify(string $signedToken, ?int $verifierUserId = null): array
    {
        try {
            $decoded = json_decode(base64_decode($signedToken), true);
            if (! is_array($decoded) || ! isset($decoded['p'], $decoded['s'])) {
                return self::auditAndReturn(false, 'invalid', $signedToken, $verifierUserId);
            }

            $expected = hash_hmac('sha256', json_encode($decoded['p']), CredentialService::signingKey());
            if (! hash_equals($expected, (string) $decoded['s'])) {
                return self::auditAndReturn(false, 'invalid', $signedToken, $verifierUserId);
            }

            $payload = $decoded['p'];
            $row = DB::table('qr_credentials')
                ->where('token_hash', hash('sha256', (string) ($payload['token'] ?? '')))
                ->first();

            if (! $row) {
                return self::auditAndReturn(false, 'invalid', $signedToken, $verifierUserId);
            }
            if ($row->status === 'revoked') {
                return self::auditAndReturn(false, 'revoked', $signedToken, $verifierUserId, $row);
            }
            if ($row->expires_at && now()->greaterThan($row->expires_at)) {
                DB::table('qr_credentials')->where('id', $row->id)->update(['status' => 'expired']);
                return self::auditAndReturn(false, 'expired', $signedToken, $verifierUserId, $row);
            }

            return self::auditAndReturn(true, 'valid', $signedToken, $verifierUserId, $row);
        } catch (\Throwable $e) {
            return ['valid' => false, 'status' => 'invalid'];
        }
    }

    private static function auditAndReturn(bool $valid, string $status, string $signed, ?int $verifierId, $row = null): array
    {
        $holderName = null;
        $holderNinHash = null;
        $issuer = null;

        if ($row) {
            $holder = null;
            if (! empty($row->user_id)) {
                $holder = \App\Models\User::find($row->user_id);
            }
            if ($holder) {
                $holderName = $holder->display_name;
                $holderNinHash = $holder->nin_hash;
            }
            try {
                $p = json_decode(base64_decode($signed), true)['p'] ?? [];
                $issuer = $p['issuer'] ?? null;
            } catch (\Throwable $ignored) {
            }

            try {
                DB::table('verification_logs')->insert([
                    'credential_id' => $row->id,
                    'verifier_user_id' => $verifierId,
                    'holder_nin_hash' => $holderNinHash,
                    'ip_address' => request()->ip(),
                    'result' => $status,
                    'scanned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $ignored) {
            }

            return [
                'valid' => $valid,
                'status' => $status,
                'holder_name' => $holderName,
                'credential_type' => $row->credential_type,
                'expires_at' => $row->expires_at ? (string) $row->expires_at : null,
                'issuer' => $issuer,
            ];
        }

        return ['valid' => $valid, 'status' => $status];
    }
}
