<?php

namespace App\Services;

class QrCodeService
{
    /**
     * Generate QR code data URL for patient DHP ID
     */
    public static function generateQrCode(string $dhpId): string
    {
        try {
            // Using simple-qrcode library
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)
                ->margin(10)
                ->generate($dhpId);
        } catch (\Exception $e) {
            \Log::error("QR Code generation failed", [
                "error" => $e->getMessage(),
                "dhp_id" => $dhpId,
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR code SVG for patient DHP ID
     */
    public static function generateQrCodeSvg(string $dhpId): string
    {
        try {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)
                ->margin(10)
                ->format("svg")
                ->generate($dhpId);
        } catch (\Exception $e) {
            \Log::error("QR Code SVG generation failed", [
                "error" => $e->getMessage(),
                "dhp_id" => $dhpId,
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR code with additional patient info as JSON
     */
    public static function generateQrCodeWithData(string $dhpId, array $data = []): string
    {
        $qrData = [
            "dhp_id" => $dhpId,
            "generated_at" => now()->toIso8601String(),
            ...$data,
        ];

        try {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::size(400)
                ->margin(15)
                ->generate(json_encode($qrData));
        } catch (\Exception $e) {
            \Log::error("QR Code with data generation failed", [
                "error" => $e->getMessage(),
                "dhp_id" => $dhpId,
            ]);
            throw $e;
        }
    }

    /**
     * Parse QR code data
     */
    public static function parseQrCodeData(string $qrData): array
    {
        try {
            $decoded = json_decode($qrData, true);
            
            // If JSON, return decoded
            if (is_array($decoded)) {
                return $decoded;
            }

            // If plain string, assume it''s the DHP ID
            return ["dhp_id" => $qrData];
        } catch (\Exception $e) {
            \Log::error("QR Code parsing failed", [
                "error" => $e->getMessage(),
            ]);
            
            return ["dhp_id" => $qrData];
        }
    }
}
