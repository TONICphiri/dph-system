<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('cors.enabled', false)) {
            return $next($request);
        }

        $response = $next($request);

        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        // If specific origins are configured, validate against them
        if (! empty($allowedOrigins) && $origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } elseif (empty($allowedOrigins)) {
            // Allow all (not recommended for production, but fallback)
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $allowedMethods = config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);
        $response->headers->set('Access-Control-Allow-Methods', implode(',', $allowedMethods));

        $allowedHeaders = config('cors.allowed_headers', [
            'Origin',
            'Content-Type',
            'Accept',
            'Authorization',
            'X-Requested-With',
            'X-CSRF-TOKEN',
        ]);
        $response->headers->set('Access-Control-Allow-Headers', implode(',', $allowedHeaders));

        $exposedHeaders = config('cors.exposed_headers', []);
        if (! empty($exposedHeaders)) {
            $response->headers->set('Access-Control-Expose-Headers', implode(',', $exposedHeaders));
        }

        if (config('cors.allow_credentials', false)) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $maxAge = config('cors.max_age');
        if ($maxAge) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }

        // Handle preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $response->headers->all());
        }

        return $response;
    }
}
