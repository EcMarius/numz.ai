<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Strict-Transport-Security (HSTS)
        if (config('security.headers.hsts.enabled', true)) {
            $maxAge = config('security.headers.hsts.max_age', 31536000);
            $includeSubDomains = config('security.headers.hsts.include_subdomains', true) ? '; includeSubDomains' : '';
            $preload = config('security.headers.hsts.preload', true) ? '; preload' : '';

            $response->headers->set(
                'Strict-Transport-Security',
                "max-age={$maxAge}{$includeSubDomains}{$preload}"
            );
        }

        // X-Frame-Options
        if (config('security.headers.x_frame_options.enabled', true)) {
            $response->headers->set(
                'X-Frame-Options',
                config('security.headers.x_frame_options.value', 'SAMEORIGIN')
            );
        }

        // X-Content-Type-Options
        if (config('security.headers.x_content_type_options.enabled', true)) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // X-XSS-Protection
        if (config('security.headers.x_xss_protection.enabled', true)) {
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        }

        // Referrer-Policy
        if (config('security.headers.referrer_policy.enabled', true)) {
            $response->headers->set(
                'Referrer-Policy',
                config('security.headers.referrer_policy.value', 'strict-origin-when-cross-origin')
            );
        }

        // Content-Security-Policy
        if (config('security.headers.csp.enabled', false)) {
            $csp = $this->buildCSP();
            if ($csp) {
                $response->headers->set('Content-Security-Policy', $csp);
            }
        }

        // Permissions-Policy
        if (config('security.headers.permissions_policy.enabled', true)) {
            $policy = $this->buildPermissionsPolicy();
            if ($policy) {
                $response->headers->set('Permissions-Policy', $policy);
            }
        }

        // Remove X-Powered-By header
        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Build Content Security Policy
     */
    protected function buildCSP(): ?string
    {
        $directives = config('security.headers.csp.directives', [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'self'"],
        ]);

        $csp = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources)) {
                $csp[] = $directive . ' ' . implode(' ', $sources);
            }
        }

        return implode('; ', $csp);
    }

    /**
     * Build Permissions Policy
     */
    protected function buildPermissionsPolicy(): ?string
    {
        $policies = config('security.headers.permissions_policy.directives', [
            'geolocation' => 'self',
            'microphone' => 'none',
            'camera' => 'none',
            'payment' => 'self',
            'usb' => 'none',
            'magnetometer' => 'none',
            'gyroscope' => 'none',
        ]);

        $policy = [];
        foreach ($policies as $feature => $allowlist) {
            if ($allowlist === 'none') {
                $policy[] = "{$feature}=()";
            } elseif ($allowlist === 'self') {
                $policy[] = "{$feature}=(self)";
            } elseif ($allowlist === '*') {
                $policy[] = "{$feature}=(*)";
            }
        }

        return implode(', ', $policy);
    }
}
