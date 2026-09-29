<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Assisted identity confirmation for issuer access to a citizen.
 * The issuer must match at least two demographic fields supplied by the
 * citizen (or their ID document). Only a timestamp is kept in the
 * session; raw submitted values are never stored.
 */
class DhpIdentityConfirmation
{
    public const REQUIRED_MATCHES = 2;

    public const SESSION_TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    /**
     * Confirmable fields and their normalized comparison.
     *
     * @return array<string, callable(Citizen, string): bool>
     */
    public static function fields(): array
    {
        $text = fn (string $stored, string $given): bool => self::norm($stored) !== '' && self::norm($stored) === self::norm($given);

        return [
            'first_name' => fn (Citizen $c, string $v): bool => $text($c->first_name, $v),
            'last_name' => fn (Citizen $c, string $v): bool => $text($c->last_name, $v),
            'date_of_birth' => fn (Citizen $c, string $v): bool => $c->date_of_birth?->format('Y-m-d') === trim($v),
            'sex' => fn (Citizen $c, string $v): bool => strtolower($c->sex) === strtolower(trim($v)),
            'district' => fn (Citizen $c, string $v): bool => $text($c->district, $v),
            'village' => fn (Citizen $c, string $v): bool => $c->village !== null && $text($c->village, $v),
        ];
    }

    /**
     * Count how many of the supplied non-empty fields match the record.
     */
    public static function countMatches(Citizen $citizen, array $supplied): int
    {
        $matches = 0;

        foreach (self::fields() as $field => $compare) {
            $value = trim((string) ($supplied[$field] ?? ''));

            if ($value !== '' && $compare($citizen, $value)) {
                $matches++;
            }
        }

        return $matches;
    }

    public static function sessionKey(int $citizenId): string
    {
        return "dhp.confirmed_citizen.{$citizenId}";
    }

    public static function grant(int $citizenId): void
    {
        session()->put(self::sessionKey($citizenId), now()->toImmutable()->toDateTimeString());
    }

    public static function confirmed(int $citizenId): bool
    {
        $at = session(self::sessionKey($citizenId));

        return $at !== null && now()->diffInMinutes($at) < self::SESSION_TTL_MINUTES;
    }

    public static function revoke(int $citizenId): void
    {
        session()->forget(self::sessionKey($citizenId));
    }

    public static function throttleKey(User $issuer, int $citizenId, string $ip): string
    {
        return "dhp-confirm:{$issuer->id}:{$citizenId}:{$ip}";
    }

    public static function tooManyAttempts(User $issuer, int $citizenId, string $ip): bool
    {
        return RateLimiter::tooManyAttempts(self::throttleKey($issuer, $citizenId, $ip), self::MAX_ATTEMPTS);
    }

    public static function hit(User $issuer, int $citizenId, string $ip): void
    {
        RateLimiter::hit(self::throttleKey($issuer, $citizenId, $ip), 600);
    }

    public static function clear(User $issuer, int $citizenId, string $ip): void
    {
        RateLimiter::clear(self::throttleKey($issuer, $citizenId, $ip));
    }

    private static function norm(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');
    }
}
