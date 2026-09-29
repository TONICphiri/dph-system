<?php

namespace App\Enums;

enum VerificationMethod: string
{
    use HasOptions;

    case QrScan = 'qr_scan';
    case ManualCode = 'manual_code';

    public function label(): string
    {
        return match ($this) {
            self::QrScan => 'QR scan',
            self::ManualCode => 'Manual code',
        };
    }
}
