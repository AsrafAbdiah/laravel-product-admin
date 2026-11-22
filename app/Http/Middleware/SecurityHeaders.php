<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers for every response.
 *
 * The Content-Security-Policy only allows scripts we serve ourselves, plus
 * inline scripts that carry this request's random nonce, so a script
 * injected into a page (XSS) won't run. Notes on the exceptions:
 * - 'unsafe-eval': Alpine.js evaluates its x-* expressions at runtime.
 * - style 'unsafe-inline': Alpine toggles inline styles, and the font
 *   loader emits an inline <style>. Styles can't run code.
 * - The Swagger UI page gets no CSP: it relies on its own inline scripts.
 * - While the Vite dev server runs (npm run dev) the CSP is skipped, as
 *   it serves scripts from another port.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($this->wantsCsp($request, $response)) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]));
        }

        return $response;
    }

    private function wantsCsp(Request $request, Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->is('api/documentation*', 'docs*')
            && ! Vite::isRunningHot();
    }
}
