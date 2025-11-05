<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QueueWorkerService
{
    protected const CACHE_KEY = 'queue_worker_running';
    protected const CACHE_DURATION = 300; // 5 minutes

    /**
     * Check if queue worker is running
     */
    public function isRunning(): bool
    {
        // Check cache first
        if (Cache::has(self::CACHE_KEY)) {
            return true;
        }

        // Check if actual process is running
        return $this->checkProcess();
    }

    /**
     * Check if queue worker process is actually running
     */
    protected function checkProcess(): bool
    {
        $output = [];
        $returnVar = 0;

        // Check for running queue:work process
        \exec('ps aux | grep "queue:work" | grep -v grep', $output, $returnVar);

        $isRunning = !empty($output);

        // Update cache if running
        if ($isRunning) {
            Cache::put(self::CACHE_KEY, true, self::CACHE_DURATION);
        }

        return $isRunning;
    }

    /**
     * Start the queue worker in background
     */
    public function start(): bool
    {
        // Don't start if already running
        if ($this->isRunning()) {
            Log::info('Queue worker already running, skipping start');
            return true;
        }

        try {
            $phpPath = $this->getPhpPath();
            $artisanPath = base_path('artisan');

            // Build command to run in background with nohup for persistence
            $command = \sprintf(
                'nohup %s %s queue:work --tries=1 --timeout=300 > /dev/null 2>&1 & echo $!',
                \escapeshellarg($phpPath),
                \escapeshellarg($artisanPath)
            );

            // Execute in background and get PID
            $pid = \exec($command);

            // Immediately mark as running in cache (optimistic approach)
            // Don't wait/verify to avoid blocking the request
            Cache::put(self::CACHE_KEY, true, self::CACHE_DURATION);

            Log::info('Queue worker start command issued', ['pid' => $pid]);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to start queue worker: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get PHP executable path
     */
    protected function getPhpPath(): string
    {
        // Try to detect PHP path
        if (defined('PHP_BINARY') && PHP_BINARY) {
            return PHP_BINARY;
        }

        // Fallback to common paths
        $commonPaths = [
            '/Applications/MAMP/bin/php/php8.4.1/bin/php',
            '/usr/bin/php',
            '/usr/local/bin/php',
            'php',
        ];

        foreach ($commonPaths as $path) {
            if (\file_exists($path)) {
                return $path;
            }
        }

        return 'php'; // Fallback to system php
    }

    /**
     * Ensure queue worker is running, start if needed
     */
    public function ensureRunning(): void
    {
        if (!$this->isRunning()) {
            Log::info('Queue worker not detected, attempting to start...');
            $this->start();
        }
    }

    /**
     * Update heartbeat cache (called by running worker)
     */
    public function heartbeat(): void
    {
        Cache::put(self::CACHE_KEY, true, self::CACHE_DURATION);
    }
}
