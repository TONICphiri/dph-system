<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompressResponseMiddleware
{
    /**
     * Compress text responses when the client asks for it. Nginx may also do
     * this; when it does, the response already carries Content-Encoding and is
     * left alone.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.response_compression', false)) {
            return $next($request);
        }

        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();

        if ($this->accepts($request, 'br') && function_exists('brotli_compress')) {
            $compressed = @brotli_compress($content);
            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'br');
            }
        } elseif ($this->accepts($request, 'gzip')) {
            $compressed = gzencode($content, 6);
            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
            }
        }

        if ($response->headers->has('Content-Encoding')) {
            $response->headers->set('Vary', 'Accept-Encoding', false);
            $response->headers->remove('Content-Length');
        }

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        if ($request->isMethod('HEAD') || $response->isRedirection()) {
            return false;
        }

        if (in_array($response->getStatusCode(), [204, 304], true)) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        $cacheControl = (string) $response->headers->get('Cache-Control', '');
        if (str_contains(strtolower($cacheControl), 'no-transform')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        $compressible = false;

        foreach ([
            'text/html',
            'text/plain',
            'text/css',
            'text/javascript',
            'application/javascript',
            'application/json',
            'application/xml',
            'text/xml',
            'image/svg+xml',
        ] as $type) {
            if (str_contains($contentType, $type)) {
                $compressible = true;
                break;
            }
        }

        if (! $compressible) {
            return false;
        }

        return strlen((string) $response->getContent()) >= 1024;
    }

    private function accepts(Request $request, string $encoding): bool
    {
        $header = strtolower((string) $request->headers->get('Accept-Encoding', ''));

        foreach (explode(',', $header) as $part) {
            [$name, $parameters] = array_pad(explode(';', trim($part), 2), 2, '');

            if (trim($name) !== $encoding) {
                continue;
            }

            if (preg_match('/q=([0-9.]+)/', $parameters, $matches)) {
                return (float) $matches[1] > 0;
            }

            return true;
        }

        return false;
    }
}
