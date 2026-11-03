<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.response_compression', false)) {
            return $next($request);
        }

        $response = $next($request);

        // Don't compress streaming responses or already compressed content
        if ($response->isStreamed()) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type');
        $compressibleTypes = [
            'text/html',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
            'image/svg+xml',
        ];

        $shouldCompress = false;
        foreach ($compressibleTypes as $type) {
            if (str_contains((string) $contentType, $type)) {
                $shouldCompress = true;
                break;
            }
        }

        if ($shouldCompress && $response->headers->get('Content-Encoding') === null) {
            $acceptEncoding = $request->headers->get('Accept-Encoding', '');

            if (str_contains($acceptEncoding, 'gzip')) {
                $response->setContent(gzencode($response->getContent(), 6));
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Vary', 'Accept-Encoding');
                $response->headers->remove('Content-Length');
            } elseif (str_contains($acceptEncoding, 'br')) {
                $compressed = @brotli_compress($response->getContent());
                if ($compressed !== false) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'br');
                    $response->headers->set('Vary', 'Accept-Encoding');
                    $response->headers->remove('Content-Length');
                }
            }
        }

        return $response;
    }
}
