<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Add CORS headers only for explicitly allowed origins.
     *
     * This is a server-rendered, same-origin application, so CORS is disabled
     * by default. It is kept for installations that publish an API to another
     * origin.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('cors.enabled', false)) {
            return $response;
        }

        $origin = $request->headers->get('Origin');
        $allowedOrigins = (array) config('cors.allowed_origins', []);
        $originAllowed = $origin && (
            in_array('*', $allowedOrigins, true)
            || in_array($origin, $allowedOrigins, true)
        );

        // Cache a response per origin even when the origin is refused, so an
        // intermediary cannot serve an allowed origin's headers to another one.
        if ($origin) {
            $response->headers->set('Vary', 'Origin', false);
        }

        if ($originAllowed) {
            // Never combine a wildcard origin with credentials.
            $allowAny = in_array('*', $allowedOrigins, true);
            $response->headers->set(
                'Access-Control-Allow-Origin',
                $allowAny && ! config('cors.allow_credentials', false) ? '*' : $origin
            );

            $response->headers->set(
                'Access-Control-Allow-Methods',
                implode(',', (array) config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']))
            );

            $response->headers->set(
                'Access-Control-Allow-Headers',
                implode(',', (array) config('cors.allowed_headers', [
                    'Origin',
                    'Content-Type',
                    'Accept',
                    'Authorization',
                    'X-Requested-With',
                    'X-CSRF-TOKEN',
                ]))
            );

            $exposedHeaders = (array) config('cors.exposed_headers', []);
            if ($exposedHeaders !== []) {
                $response->headers->set('Access-Control-Expose-Headers', implode(',', $exposedHeaders));
            }

            if (config('cors.allow_credentials', false)) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }

            $maxAge = config('cors.max_age');
            if ($maxAge) {
                $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
            }
        }

        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(204);
            $response->setContent('');
        }

        return $response;
    }
}
