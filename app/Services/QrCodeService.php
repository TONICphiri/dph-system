<?php

namespace App\Services;

use App\Models\Credential;
use App\Models\Patient;
use SimpleSoftwareIO\QrCode\Generator;

/**
 * Builds the QR code printed on the health passport card. The code holds
 * only a random token, never personal or medical information, so a lost
 * card reveals nothing without access to the system.
 */
class QrCodeService
{
    public const PREFIX = 'DHP:';

    public function forPatient(Patient $patient, int $size = 160): string
    {
        $svg = (string) (new Generator)
            ->format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate(self::PREFIX.$patient->qr_token);

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    /**
     * Builds the QR code printed on a credential certificate. Encodes only
     * the verification URL with a random opaque token, never personal or
     * medical information. Verifier sees minimal data.
     */
    public function forCredential(Credential $credential, int $size = 160): string
    {
        $svg = (string) (new Generator)
            ->format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate(url('/verify/by-token/'.$credential->qr_token));

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }
    public function tokenFromScan(string $value): ?string
    {
        $value = trim($value);

        return str_starts_with($value, self::PREFIX) ? substr($value, strlen(self::PREFIX)) : null;
    }
}
