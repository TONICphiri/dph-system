<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use App\Services\ErrorReferenceService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $exception, $request) {
            $errorService = app(ErrorReferenceService::class);
            $referenceId = $errorService->logError($exception);

            $status = 500;

            if ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
            } elseif ($exception instanceof ValidationException) {
                $status = 422;
            } elseif (method_exists($exception, 'getStatusCode')) {
                $status = $exception->getStatusCode();
            }

            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            $message = match ($status) {
                400 => 'Bad request. Please check your input.',
                401 => 'Unauthorized. Please log in and try again.',
                403 => 'You do not have permission to access this page.',
                404 => 'The requested page could not be found.',
                419 => 'The page has expired. Please refresh and try again.',
                429 => 'Too many requests. Please slow down.',
                503 => 'The service is currently unavailable. Please try again later.',
                default => 'An unexpected error occurred. Please contact support with this reference.',
            };

            if ($request->expectsJson() || $request->wantsJson() || $request->isJson()) {
                $payload = [
                    'message' => $message,
                    'referenceId' => $referenceId,
                ];

                if ($exception instanceof ValidationException) {
                    $payload['errors'] = $exception->errors();
                }

                return response()->json($payload, $status);
            }

            $viewName = "errors.{$status}";

            if (!view()->exists($viewName)) {
                $viewName = 'errors.500';
                $status = 500;
            }

            return response()->view($viewName, [
                'message' => $message,
                'reference' => $referenceId,
            ], $status);
        });
    })->create();