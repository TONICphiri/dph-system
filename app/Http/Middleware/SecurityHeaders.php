<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adds standard defensive HTTP headers to every response.
     * Especially important here since the system serves patient health data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
        // camera=(self) is left open because the QR scanner uses getUserMedia on this origin.

        if ($request->secure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content-Security-Policy: allow the CDN scripts this app already references
        // (fontawesome/bootstrap CDN in test.html, tailwind CDN) plus 'self'.
        // Tighten this further if you remove the CDN <script> tags from test.html/error pages.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "img-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
