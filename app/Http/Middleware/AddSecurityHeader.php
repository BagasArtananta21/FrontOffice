<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $r2Url = config('filesystems.r2_public_url');

        $csp = ["frame-ancestors 'self'"];
        if ($r2Url !== '') {
            $csp[] = "media-src 'self' {$r2Url}";
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
