<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\Credential;
use Illuminate\Support\Str;

/**
 * Unique passport identifiers. Retries on collision at both the
 * application level and (via unique indexes) the database level.
 */
class DhpIdentifierService
{
    /**
     * MW-DHP-YYYY-XXXXXX
     */
    public function nextPassportId(): string
    {
        $year = now()->format('Y');
        $prefix = "MW-DHP-{$year}-";

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $id = $prefix.Str::padLeft((string) random_int(0, 999999), 6, '0');

            if (! Citizen::query()->where('passport_id', $id)->exists()) {
                return $id;
            }
        }

        throw new \RuntimeException('Could not generate a unique passport ID.');
    }

    /**
     * MW-CRED-YYYY-XXXXXX
     */
    public function nextCredentialNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "MW-CRED-{$year}-";

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $number = $prefix.Str::padLeft((string) random_int(0, 999999), 6, '0');

            if (! Credential::query()->where('credential_number', $number)->exists()) {
                return $number;
            }
        }

        throw new \RuntimeException('Could not generate a unique credential number.');
    }

    /**
     * Random opaque 64-char token. Contains NO personal data.
     * National ID is an identifier, never a password, PIN seed or QR content.
     */
    public function newQrToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Credential::query()->where('qr_token', $token)->exists());

        return $token;
    }
}
