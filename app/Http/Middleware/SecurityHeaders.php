<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers.
 *
 * The app previously sent none of these. They are cheap, they are what a technical
 * SEO or security scan looks for first, and two of them close real attack classes
 * rather than just satisfying a checklist.
 *
 * CSP ships in Report-Only mode — see cspReportOnly() below for why.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Stop browsers second-guessing Content-Type. Chiefly this prevents a file
        // uploaded to the gallery from being sniffed and executed as something else.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Clickjacking: the storefront has add-to-cart and checkout forms, which are
        // exactly what a framed overlay attack targets. SAMEORIGIN, not DENY, so the
        // admin's own previews keep working.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Send the full URL to ourselves, origin-only cross-site. Keeps analytics
        // referrers useful without leaking customer order or account paths outward.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Opt out of Google's FLoC/Topics-era interest cohorts; harmless where unsupported.
        $response->headers->set('Permissions-Policy', 'browsing-topics=()');

        /*
         | HSTS is a one-way door: once a browser has seen it, it refuses plain HTTP
         | to this host for max-age seconds, and there is no way to take that back
         | early. So it is sent only on connections that are already secure, and only
         | outside local development. `preload` is deliberately omitted — submitting
         | to the preload list is effectively permanent and is the site owner's call,
         | not a default.
         */
        if ($request->secure() && ! app()->environment('local', 'testing')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=' . (int) config('app.hsts_max_age', 31536000) . '; includeSubDomains'
            );
        }

        if (config('app.csp_report_only', true) && $this->isHtml($response)) {
            $response->headers->set('Content-Security-Policy-Report-Only', $this->cspReportOnly());
        }

        return $response;
    }

    /** Only worth attaching a CSP to documents, not JSON or file downloads. */
    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }

    /**
     * Report-Only, not enforcing — this header never blocks anything, it only makes
     * the browser log what a real policy WOULD have blocked. That is the honest way
     * to introduce a CSP to an app like this one: the storefront and admin are built
     * on inline Alpine expressions, inline <style> blocks and inline JSON-LD, so an
     * enforcing policy added blind would quietly break the cart, the gallery uploader
     * and the support chat, and you would find out from customers rather than tests.
     *
     * `unsafe-inline` is kept for script and style precisely because removing it is
     * the big job (every inline handler needs a nonce). What this still catches, and
     * what makes it worth shipping today, is the class of thing you cannot see by
     * reading the code: a script injected from a domain you never approved, a form
     * posting somewhere unexpected, your pages being framed elsewhere.
     *
     * Read violations in DevTools → Console. When the log is quiet, tighten a
     * directive here, watch again, and only then switch the header name to
     * `Content-Security-Policy` to start enforcing.
     */
    private function cspReportOnly(): string
    {
        // Analytics is loaded conditionally, and Reverb needs a websocket origin.
        $reverb = sprintf(
            '%s://%s:%s',
            config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'wss' : 'ws',
            config('broadcasting.connections.reverb.options.host') ?: 'localhost',
            config('broadcasting.connections.reverb.options.port') ?: 8080,
        );

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            // Remote images: seeded placeholders plus whatever a product photo points at.
            "img-src 'self' data: blob: https:",
            "connect-src 'self' {$reverb} https://www.google-analytics.com",
            "frame-src 'self' https://www.youtube.com https://www.google.com",
        ]);
    }
}
