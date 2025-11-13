<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpWhitelistMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get whitelist from config
        $whitelist = config('security.ip_whitelist', []);

        // If whitelist is empty, allow all (disabled)
        if (empty($whitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        // Check if IP is in whitelist
        if (!$this->isIpWhitelisted($clientIp, $whitelist)) {
            \Log::warning('IP blocked by whitelist', [
                'ip' => $clientIp,
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Your IP address is not authorized to access this resource.'
                ], 403);
            }

            abort(403, 'Your IP address is not authorized to access this resource.');
        }

        return $next($request);
    }

    /**
     * Check if IP is whitelisted
     */
    protected function isIpWhitelisted(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $whitelistedIp) {
            // Exact match
            if ($ip === $whitelistedIp) {
                return true;
            }

            // CIDR notation support
            if (str_contains($whitelistedIp, '/')) {
                if ($this->ipInRange($ip, $whitelistedIp)) {
                    return true;
                }
            }

            // Wildcard support (e.g., 192.168.1.*)
            if (str_contains($whitelistedIp, '*')) {
                $pattern = str_replace('.', '\.', $whitelistedIp);
                $pattern = str_replace('*', '\d{1,3}', $pattern);
                if (preg_match("/^{$pattern}$/", $ip)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }
}
