<?php

namespace App\Exceptions;

use App\Services\ErrorReferenceService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Central DHP exception policy:
 * - Never expose stack traces or PHI to end users.
 * - Always log with a trackable reference ID.
 * - Return consistent, accessible error feedback (web + JSON).
 */
class DhpExceptionHandler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        Log::error('DHP exception', [
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        // Let Laravel handle validation + auth redirects natively so
        // inline form errors keep working (HCI: preserve user input).
        if ($e instanceof \Illuminate\Validation\ValidationException
            || $e instanceof \Illuminate\Auth\AuthenticationException) {
            return parent::render($request, $e);
        }

        $reference = app(ErrorReferenceService::class)->logError($e);
        $status = method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Something went wrong. Reference: ' . $reference,
                'reference' => $reference,
            ], $status);
        }

        $view = view()->exists("errors.{$status}") ? "errors.{$status}" : 'errors.500';

        return response()->view($view, [
            'message' => 'Something went wrong. Please try again.',
            'reference' => $reference,
        ], $status);
    }
}
