<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('security.headers.enabled', false)) {
            // Prevent clickjacking
            $response->headers->set('X-Frame-Options', config('security.headers.x_frame_options', 'DENY'));

            // Prevent MIME-type sniffing
            $response->headers->set('X-Content-Type-Options', config('security.headers.x_content_type_options', 'nosniff'));

            // XSS protection (legacy, but still useful for older browsers)
            $response->headers->set('X-XSS-Protection', config('security.headers.x_xss_protection', '1; mode=block'));

            // Referrer policy
            $response->headers->set('Referrer-Policy', config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));

            // Content Security Policy
            $csp = config('security.headers.content_security_policy');
            if ($csp) {
                $response->headers->set('Content-Security-Policy', $csp);
            }

            // Strict Transport Security (HSTS) - only when HTTPS
            if ($request->secure() || config('app.force_https', false)) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }

            // Permissions Policy
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        }

        return $response;
    }
}
