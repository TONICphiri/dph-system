<?php

use App\Console\Commands\CleanupSessionsCommand;
use App\Console\Commands\CreateAdminCommand;
use App\Exceptions\WorkflowException;
use App\Http\Middleware\CompressResponseMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\LogSlowRequestsMiddleware;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\PreventAuthenticatedResponseCaching;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->trustProxies(at: explode(',', config('app.trusted_proxies', '127.0.0.1')));

        // These checks need the session-backed user, so run inside the web group.
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', EnsurePasswordIsChanged::class);
        $middleware->appendToGroup('web', PreventAuthenticatedResponseCaching::class);

        // Security headers - applied to all web responses
        $middleware->appendToGroup('web', SecurityHeadersMiddleware::class);

        // Rate limiting - applied to all web requests
        $middleware->appendToGroup('web', RateLimitMiddleware::class.':api');

        // CORS - applied to all web requests
        $middleware->appendToGroup('web', CorsMiddleware::class);

        // Response compression - applied to all web responses
        $middleware->appendToGroup('web', CompressResponseMiddleware::class);

        // Slow request logging - applied to all web requests
        $middleware->appendToGroup('web', LogSlowRequestsMiddleware::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'security.headers' => SecurityHeadersMiddleware::class,
            'rate.limit' => RateLimitMiddleware::class,
            'cors' => CorsMiddleware::class,
        ]);

        // Rate limiter definitions
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute((int) config('security.rate_limit.limits.login', 5))
                ->by($request->ip().':'.$request->input('email', $request->input('username')))
                ->response(function () {
                    if (request()->expectsJson()) {
                        return response()->json([
                            'message' => 'Too many login attempts. Please try again later.',
                        ], 429);
                    }

                    return back()->withInput()
                        ->with('error', 'Too many login attempts. Please try again later.');
                });
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('security.rate_limit.limits.api', 60))
                ->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute((int) config('security.rate_limit.limits.register', 3))
                ->by($request->ip().':register');
        });

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withCommands([
        CreateAdminCommand::class,
        CleanupSessionsCommand::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business rule messages are expected and shown to the user, not logged.
        $exceptions->dontReport(WorkflowException::class);

        // Every logged error carries a short reference number.
        $exceptions->context(fn () => ['reference' => app()->has('error.reference')
            ? app('error.reference')
            : tap(strtoupper(Str::random(8)), fn ($reference) => app()->instance('error.reference', $reference))]);

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withInput($request->except('password', 'password_confirmation', '_token'))
                ->with('error', 'Your session expired before the form was sent. Please try again.');
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (config('app.debug')
                || $exception instanceof HttpExceptionInterface
                || $exception instanceof ModelNotFoundException
                || $exception instanceof AuthorizationException
                || $exception instanceof WorkflowException
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException) {
                return null;
            }

            $reference = app()->has('error.reference') ? app('error.reference') : null;

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Something went wrong on our side. Please try again.',
                    'reference' => $reference,
                ], 500);
            }

            return response()->view('errors.500', ['reference' => $reference], 500);
        });
    })->create();
