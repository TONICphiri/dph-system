<?php

namespace App\Services;

use App\Enums\VerificationResult;
use App\Models\Credential;

/**
 * Single decision point for credential verification. QR-token and
 * manual-number flows both call here so status logic cannot diverge.
 * Read-only: verification never mutates a credential.
 * Verifier sees minimal data (enforced in the views, never here).
 */
class DhpCredentialVerificationService
{
    /**
     * @return array{result: VerificationResult, credential: ?Credential}
     */
    public function checkByToken(string $token): array
    {
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return ['result' => VerificationResult::Invalid, 'credential' => null];
        }

        $credential = Credential::query()
            ->where('qr_token', $token)
            ->with(['citizen', 'facility'])
            ->first();

        if ($credential === null) {
            return ['result' => VerificationResult::NotFound, 'credential' => null];
        }

        return ['result' => $this->decide($credential), 'credential' => $credential];
    }

    /**
     * @return array{result: VerificationResult, credential: ?Credential}
     */
    public function checkByNumber(string $number): array
    {
        $number = strtoupper(trim($number));

        if (! preg_match('/^MW-CRED-\d{4}-\d{6}$/', $number)) {
            return ['result' => VerificationResult::Invalid, 'credential' => null];
        }

        $credential = Credential::query()
            ->where('credential_number', $number)
            ->with(['citizen', 'facility'])
            ->first();

        if ($credential === null) {
            return ['result' => VerificationResult::NotFound, 'credential' => null];
        }

        return ['result' => $this->decide($credential), 'credential' => $credential];
    }

    private function decide(Credential $credential): VerificationResult
    {
        return match ($credential->status->value) {
            'revoked' => VerificationResult::Revoked,
            'superseded' => VerificationResult::Superseded,
            default => $this->decideActive($credential),
        };
    }

    private function decideActive(Credential $credential): VerificationResult
    {
        if ($credential->expiry_date !== null && $credential->expiry_date->isPast()) {
            return VerificationResult::Expired;
        }

        if ($credential->status->value === 'expired') {
            return VerificationResult::Expired;
        }

        if ($credential->facility === null || ! $credential->facility->is_active) {
            return VerificationResult::Invalid;
        }

        return VerificationResult::Valid;
    }

    /**
     * "Chikondi Banda" -> "Ch*** Ba***". Never reveals the full name.
     */
    public static function maskName(string $fullName): string
    {
        $words = preg_split('/\s+/', trim($fullName)) ?: [];

        $masked = array_map(function (string $word): string {
            $word = trim($word);

            return mb_strlen($word) <= 2 ? '***' : mb_substr($word, 0, 2).'***';
        }, $words);

        return implode(' ', $masked);
    }

    /**
     * "MW-DHP-2026-000145" -> "MW-DHP-2026-***145".
     */
    public static function maskPassportId(string $passportId): string
    {
        if (strlen($passportId) <= 6) {
            return '***';
        }

        return substr($passportId, 0, -6).'***'.substr($passportId, -3);
    }
}
