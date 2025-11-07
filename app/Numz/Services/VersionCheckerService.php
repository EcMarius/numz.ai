<?php

namespace App\Numz\Services;

use App\Models\VersionCheck;
use App\Models\UpdateNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VersionCheckerService
{
    /**
     * Check for available updates
     */
    public function checkForUpdates(bool $force = false): VersionCheck
    {
        $currentVersion = config('updater.current_version');
        $checkUrl = config('updater.check_url');

        try {
            // Check cache first unless forced
            if (!$force) {
                $cached = Cache::get('version_check_result');
                if ($cached && $cached instanceof VersionCheck) {
                    return $cached;
                }
            }

            // Make HTTP request to check for updates
            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get($checkUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to check for updates: HTTP ' . $response->status());
            }

            $releaseInfo = $response->json();
            $latestVersion = $this->extractVersion($releaseInfo);
            $updateAvailable = $this->isNewerVersion($currentVersion, $latestVersion);

            // Create version check record
            $versionCheck = VersionCheck::create([
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'update_available' => $updateAvailable,
                'check_status' => 'success',
                'release_info' => $releaseInfo,
                'checked_at' => now(),
            ]);

            // Cache the result for 1 hour
            Cache::put('version_check_result', $versionCheck, 3600);

            // Notify admins if update is available
            if ($updateAvailable) {
                $this->notifyUpdateAvailable($versionCheck);
            }

            Log::info('Version check completed', [
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'update_available' => $updateAvailable,
            ]);

            return $versionCheck;

        } catch (\Exception $e) {
            Log::error('Version check failed', [
                'error' => $e->getMessage(),
                'current_version' => $currentVersion,
            ]);

            return VersionCheck::create([
                'current_version' => $currentVersion,
                'check_status' => 'failed',
                'error_message' => $e->getMessage(),
                'checked_at' => now(),
            ]);
        }
    }

    /**
     * Get HTTP headers for version check request
     */
    protected function getHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Numz.ai-Updater/' . config('updater.current_version'),
        ];

        if ($token = config('updater.server_token')) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Extract version from release info
     */
    protected function extractVersion(array $releaseInfo): string
    {
        // GitHub releases format
        if (isset($releaseInfo['tag_name'])) {
            return ltrim($releaseInfo['tag_name'], 'v');
        }

        // Custom format
        if (isset($releaseInfo['version'])) {
            return $releaseInfo['version'];
        }

        throw new \Exception('Could not extract version from release info');
    }

    /**
     * Compare versions (semantic versioning)
     */
    public function isNewerVersion(string $current, string $latest): bool
    {
        return version_compare($latest, $current, '>');
    }

    /**
     * Get update channel (stable, beta, alpha)
     */
    protected function getUpdateChannel(): string
    {
        return config('updater.channel', 'stable');
    }

    /**
     * Notify admins about available update
     */
    protected function notifyUpdateAvailable(VersionCheck $versionCheck): void
    {
        if (!config('updater.notifications.enabled')) {
            return;
        }

        $message = "Version {$versionCheck->latest_version} is now available! " .
                   "You are currently running version {$versionCheck->current_version}.";

        // Get admin users
        $admins = \App\Models\User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            UpdateNotification::create([
                'version' => $versionCheck->latest_version,
                'notification_type' => 'new_version',
                'message' => $message,
                'metadata' => [
                    'current_version' => $versionCheck->current_version,
                    'latest_version' => $versionCheck->latest_version,
                    'changelog' => $versionCheck->changelog,
                    'download_url' => $versionCheck->download_url,
                ],
                'user_id' => $admin->id,
            ]);
        }

        Log::info('Update notifications sent to admins', [
            'version' => $versionCheck->latest_version,
            'admin_count' => $admins->count(),
        ]);
    }

    /**
     * Get latest version check result
     */
    public function getLatestCheck(): ?VersionCheck
    {
        return VersionCheck::getLatest();
    }

    /**
     * Check if update check is needed
     */
    public function needsCheck(): bool
    {
        return VersionCheck::needsCheck();
    }

    /**
     * Get current version info
     */
    public function getCurrentVersionInfo(): array
    {
        return [
            'version' => config('updater.current_version'),
            'channel' => config('updater.channel'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
}
