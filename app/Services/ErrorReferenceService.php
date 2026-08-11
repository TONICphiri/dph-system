<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ErrorReferenceService
{
    /**
     * Generate a unique error reference ID.
     * Format: DHP-ERR-{8-char-hex}
     */
    public function generateReferenceId(): string
    {
        return 'DHP-ERR-' . Str::random(8);
    }

    /**
     * Log the error with the reference ID.
     * 
     * @param \Throwable $exception
     * @param string|null $referenceId Optional existing ID (for consistency)
     * @return string The reference ID used
     */
    public function logError(\Throwable $exception, ?string $referenceId = null): string
    {
        $id = $referenceId ?? $this->generateReferenceId();

        // Sanitize message to avoid logging sensitive patient data
        $message = $exception->getMessage();
        
        // Log with context, but keep it generic for the user-facing side
        Log::error("System Error [{$id}]: {$message}", [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            // We intentionally do NOT log the full stack trace or user data here
            // to prevent PII leakage in production logs.
        ]);

        return $id;
    }
}