<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        if (! config('security.rate_limit.enabled', false)) {
            return $next($request);
        }

        $key = $this->resolveKey($request, $type);
        $maxAttempts = config("security.rate_limit.limits.{$type}", 60);
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            if ($request->expectsJson() || $request->is('api/*')) {
                return new JsonResponse([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter,
                ], 429, ['Retry-After' => $retryAfter]);
            }

            return back()->withInput()
                ->with('error', "Too many attempts. Please try again in {$retryAfter} seconds.");
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }

    /**
     * Resolve the rate limiting key for the request.
     */
    private function resolveKey(Request $request, string $type): string
    {
        if ($type === 'login') {
            return $request->ip().':'.$request->input('email', $request->input('username'));
        }

        if ($type === 'register') {
            return $request->ip().':register';
        }

        return $request->ip().':api';
    }
}
