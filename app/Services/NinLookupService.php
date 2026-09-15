<?php

namespace App\Services;

/**
 * Hash-based catalogue search by NIN without storing raw NINs.
 * per system-description2.md §5.1, §7.2: nin_hash = HMAC-SHA256(nin, dedicated pepper).
 */
class NinLookupService
{
    public static function pepper(): string
    {
        return (string) config('auth.nin_pepper', env('NIN_PEPPER', env('APP_KEY', 'dhp-nin-pepper')));
    }

    public static function normalize(string $nin): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($nin)));
    }

    public static function hash(string $nin): string
    {
        return hash_hmac('sha256', self::normalize($nin), self::pepper());
    }

    public static function last4(string $nin): string
    {
        $digits = preg_replace('/\D/', '', $nin);
        return substr($digits, -4) ?: substr(self::normalize($nin), -4);
    }

    public static function mask(string $nin): string
    {
        return 'NIN-****-'.self::last4($nin);
    }

    public static function findByNin(string $nin): ?\App\Models\User
    {
        return \App\Models\User::where('nin_hash', self::hash($nin))->first();
    }

    public static function exists(string $nin): bool
    {
        return \App\Models\User::where('nin_hash', self::hash($nin))->exists();
    }
}
