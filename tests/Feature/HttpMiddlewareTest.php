<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The security, CORS and compression middleware run on every web request.
 * These tests keep the boot-time bugs from coming back and pin the behaviour
 * the QR scanner and the browser rely on.
 */
class HttpMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_security_headers_are_sent_and_allow_the_camera(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);

        // A bare camera=() would block the QR scanner in Chrome and Safari.
        $permissions = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=(self)', $permissions);
        $this->assertStringContainsString('microphone=()', $permissions);
    }

    public function test_text_responses_are_compressed_when_the_client_accepts_gzip(): void
    {
        config(['app.response_compression' => true]);

        $response = $this->withHeader('Accept-Encoding', 'gzip')->get('/login');

        $response->assertOk();
        $response->assertHeader('Content-Encoding', 'gzip');
        $response->assertHeader('Vary', 'Accept-Encoding');

        $html = gzdecode((string) $response->getContent());

        $this->assertIsString($html);
        $this->assertStringContainsString('Digital Health Passport', $html);
    }

    public function test_compression_is_skipped_when_the_client_does_not_ask_for_it(): void
    {
        config(['app.response_compression' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $this->assertFalse($response->headers->has('Content-Encoding'));
    }

    public function test_cors_is_off_by_default(): void
    {
        $response = $this->withHeader('Origin', 'https://example.org')->get('/login');

        $response->assertOk();
        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_cors_allows_only_an_explicit_origin_and_never_wildcards_with_credentials(): void
    {
        config([
            'cors.enabled' => true,
            'cors.allowed_origins' => ['https://example.org'],
            'cors.allow_credentials' => true,
        ]);

        $this->withHeader('Origin', 'https://example.org')
            ->get('/login')
            ->assertHeader('Access-Control-Allow-Origin', 'https://example.org')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

        $this->withHeader('Origin', 'https://attacker.example')
            ->get('/login')
            ->assertHeaderMissing('Access-Control-Allow-Origin');

        config(['cors.allowed_origins' => ['*'], 'cors.allow_credentials' => true]);

        $this->withHeader('Origin', 'https://example.org')
            ->get('/login')
            ->assertHeader('Access-Control-Allow-Origin', 'https://example.org');
    }
}
