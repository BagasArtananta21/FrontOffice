<?php

namespace Tests\Feature\HTTP;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SecurityHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function assertSecurityHeaders(TestResponse $response): void
    {
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_web_responses_carry_the_headers(): void
    {
        $this->assertSecurityHeaders($this->get('/'));
    }

    public function test_filament_panel_responses_carry_the_headers(): void
    {
        $this->assertSecurityHeaders($this->get('/admin'));
    }

    public function test_error_responses_carry_the_headers(): void
    {
        $this->assertSecurityHeaders($this->get('/tidak-ada'));
    }
}
