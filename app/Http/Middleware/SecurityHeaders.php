<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Generate Nonce Kriptografis Unik per Request
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);
        app()->instance('csp_nonce', $nonce);

        if (class_exists(Vite::class)) {
            Vite::useCspNonce($nonce);
        }

        // Livewire script tag nonce integration jika Livewire aktif
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::useScriptTagAttributes(['nonce' => $nonce]);
        }

        /** @var Response $response */
        $response = $next($request);

        // 2. Header Keamanan Standar Industri (OWASP Secure Headers)
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 3. HTTP Strict Transport Security (HSTS)
        // HANYA dipasang di environment production dan HANYA saat request diakses via HTTPS
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // 4. Content Security Policy (CSP) berbasis Nonce
        $isReportOnly = config('security.csp_report_only', env('CSP_REPORT_ONLY', true));
        $headerName = ($isReportOnly === false || $isReportOnly === 'false')
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' https://fonts.googleapis.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        $response->headers->set($headerName, implode('; ', $cspDirectives));

        return $response;
    }
}
