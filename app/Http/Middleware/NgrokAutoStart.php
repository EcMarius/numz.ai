<?php

namespace App\Http\Middleware;

use App\Services\NgrokService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class NgrokAutoStart
{
    protected $ngrokService;

    public function __construct(NgrokService $ngrokService)
    {
        $this->ngrokService = $ngrokService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in production - only run on localhost
        if (app()->environment('production')) {
            return $next($request);
        }

        // Use cache to ensure we only try to start ngrok once per session
        $cacheKey = 'ngrok_autostart_attempted';

        if (!Cache::has($cacheKey)) {
            $this->attemptNgrokStart();
            // Cache for 1 hour - ngrok will stay running
            Cache::put($cacheKey, true, now()->addHour());
        }

        return $next($request);
    }

    /**
     * Attempt to auto-start ngrok
     */
    protected function attemptNgrokStart(): void
    {
        try {
            // Only auto-start if on localhost
            if (!NgrokService::isLocalhost()) {
                return;
            }

            $this->ngrokService->autoStart();
        } catch (\Exception $e) {
            Log::debug('Ngrok auto-start middleware error: ' . $e->getMessage());
        }
    }
}
