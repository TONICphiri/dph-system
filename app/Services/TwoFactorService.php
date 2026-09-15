<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

/**
 * Time-based one-time passwords (RFC 6238, 6 digits, 30-second steps).
 * Crypto by the audited pragmarx/google2fa package; this class adds
 * session handling and single-use recovery codes around it.
 */
class TwoFactorService
{
    public const SESSION_KEY = 'two_factor_verified_at';
    public const SESSION_TTL_MINUTES = 720; // 12 hours

    protected static function engine(): Google2FA
    {
        return app(Google2FA::class);
    }

    public static function generateSecret(): string
    {
        // 32 base32 chars = 160 bits, matching the HMAC-SHA1 key size.
        return static::engine()->generateSecretKey(32);
    }

    public static function provisioningUri(string $account, string $secret, string $issuer = 'DHP'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account)
            .'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&digits=6&period=30';
    }

    /** Verify a 6-digit code, accepting one step of clock drift either way. */
    public static function verify(string $secret, string $code, ?int $now = null): bool
    {
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        try {
            // The engine takes a counter here, not unix time.
            $counter = $now === null ? null : (int) floor($now / 30);

            return (bool) static::engine()->verifyKey($secret, $code, 1, $counter);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Code for a given moment (handy for tests and setup confirmation screens). */
    public static function currentCode(string $secret, ?int $now = null): string
    {
        return static::engine()->oathTotp($secret, (int) floor(($now ?? time()) / 30));
    }

    public static function markSessionVerified(): void
    {
        session([self::SESSION_KEY => time()]);
    }    public static function sessionVerified(): bool
    {
        $at = (int) session(self::SESSION_KEY, 0);

        return $at > 0 && (time() - $at) < self::SESSION_TTL_MINUTES * 60;
    }

    public static function clearSession(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** Generate 10 single-use recovery codes (shown once, stored hashed). */
    public static function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 5).'-'.substr(bin2hex(random_bytes(5)), 0, 5));
        }

        return $codes;
    }

    /** SHA-256 hashes of recovery codes for storage (never store plaintext). */
    public static function hashRecoveryCodes(array $codes): string
    {
        return json_encode(array_values(array_map(
            fn ($c) => hash('sha256', strtoupper(str_replace([' ', '-'], '', trim($c)))),
            $codes
        )));
    }

    /**
     * Accept and burn one recovery code. Returns false when unknown/spent.
     * Comparison is hash-only: plaintext codes never touch the database.
     */
    public static function consumeRecoveryCode($user, string $code): bool
    {
        $stored = json_decode((string) $user->two_factor_recovery_codes, true);

        if (! is_array($stored) || $stored === []) {
            return false;
        }

        $candidate = hash('sha256', strtoupper(str_replace([' ', '-'], '', trim($code))));

        foreach ($stored as $i => $hash) {
            if (hash_equals((string) $hash, $candidate)) {
                unset($stored[$i]);
                $user->forceFill(['two_factor_recovery_codes' => json_encode(array_values($stored))])->saveQuietly();

                return true;
            }
        }

        return false;
    }

    public static function remainingRecoveryCodes($user): int
    {
        $stored = json_decode((string) $user->two_factor_recovery_codes, true);

        return is_array($stored) ? count($stored) : 0;
    }
}
