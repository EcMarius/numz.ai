<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EnsureQueueWorkerRunning
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check on web routes, not API or console
        if ($request->is('api/*') || app()->runningInConsole()) {
            return $next($request);
        }

        // Only check if queue connection is database (not sync)
        if (config('queue.default') !== 'database') {
            return $next($request);
        }

        // Use cache to avoid checking every request (check every 2 minutes)
        $cacheKey = 'queue_worker_check';

        if (!Cache::has($cacheKey)) {
            // Mark as checked for next 2 minutes
            Cache::put($cacheKey, true, 120);

            // Start queue worker in background (fire and forget)
            try {
                $this->startQueueWorkerAsync();
            } catch (\Exception $e) {
                Log::error('Failed to start queue worker: ' . $e->getMessage());
                // Don't fail the request, just log the error
            }
        }

        return $next($request);
    }

    /**
     * Start queue worker asynchronously without blocking the request
     */
    protected function startQueueWorkerAsync(): void
    {
        $phpPath = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : '/Applications/MAMP/bin/php/php8.4.1/bin/php';
        $artisanPath = base_path('artisan');
        $logPath = storage_path('logs/queue-worker.log');

        // Quick check if worker is already running (non-blocking)
        $checkCommand = 'ps aux | grep "queue:work" | grep -v grep | wc -l';
        $count = (int)trim(shell_exec($checkCommand));

        if ($count > 0) {
            Log::debug('Queue worker already running', ['count' => $count]);
            return;
        }

        // Start worker in background with nohup for persistence
        $command = sprintf(
            'nohup %s %s queue:work --tries=1 --timeout=300 >> %s 2>&1 &',
            escapeshellarg($phpPath),
            escapeshellarg($artisanPath),
            escapeshellarg($logPath)
        );

        Log::info('Starting queue worker', ['command' => $command]);

        // Execute without waiting (fire and forget)
        shell_exec($command);
    }
}
