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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $start) * 1000; // milliseconds

        $threshold = (int) config('app.performance.slow_request_threshold_ms', 1000);

        if ($duration > $threshold) {
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'user_id' => $request->user() ? $request->user()->id : null,
                'status' => $response->getStatusCode(),
            ]);
        }

        // Only expose timing while developing; a production response should not
        // tell a client how long the server took.
        if (config('app.debug')) {
            $response->headers->set('X-Response-Time', round($duration, 2).' ms');
        }

        return $response;
    }
}
