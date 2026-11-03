<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequestsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000; // milliseconds

        $threshold = (int) config('performance.slow_request_threshold_ms', 1000);

        if ($duration > $threshold) {
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'uri' => $request->fullUrl(),
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'status' => $response->getStatusCode(),
            ]);
        }

        // Add response timing header for debugging
        $response->headers->set('X-Response-Time', round($duration, 2).' ms');

        return $response;
    }
}
