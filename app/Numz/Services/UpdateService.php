<?php

namespace App\Numz\Services;

use App\Models\SystemUpdate;
use App\Models\UpdateBackup;
use App\Models\UpdateNotification;
use App\Models\VersionCheck;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UpdateService
{
    protected BackupService $backupService;
    protected string $downloadPath;
    protected string $tempPath;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        $this->downloadPath = config('updater.download_path', storage_path('app/updates'));
        $this->tempPath = storage_path('app/temp');

        // Ensure directories exist
        if (!File::exists($this->downloadPath)) {
            File::makeDirectory($this->downloadPath, 0755, true);
        }
        if (!File::exists($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0755, true);
        }
    }

    /**
     * Start the update process
     */
    public function applyUpdate(VersionCheck $versionCheck, ?int $userId = null): SystemUpdate
    {
        Log::info('Starting update process', [
            'version' => $versionCheck->latest_version,
            'user_id' => $userId,
        ]);

        // Create system update record
        $systemUpdate = SystemUpdate::create([
            'version' => $versionCheck->latest_version,
            'previous_version' => config('updater.current_version'),
            'update_type' => $this->determineUpdateType(config('updater.current_version'), $versionCheck->latest_version),
            'status' => 'pending',
            'changelog' => $versionCheck->changelog,
            'download_url' => $versionCheck->download_url,
            'checksum' => $versionCheck->release_info['checksum'] ?? null,
            'download_size' => $versionCheck->release_info['size'] ?? null,
            'initiated_by' => $userId,
            'auto_update' => false,
        ]);

        try {
            // Step 1: Pre-update checks
            $systemUpdate->updateProgress(5, 'Running pre-update checks');
            $this->runPreUpdateChecks();

            // Step 2: Enable maintenance mode
            if (config('updater.maintenance_mode', true)) {
                $systemUpdate->updateProgress(10, 'Enabling maintenance mode');
                $this->enableMaintenanceMode();
            }

            // Step 3: Create backup
            if (config('updater.backup_before_update', true)) {
                $systemUpdate->updateProgress(15, 'Creating backup');
                $backup = $this->backupService->createBackup($systemUpdate);
                $systemUpdate->update([
                    'backup_info' => [
                        'backup_id' => $backup->id,
                        'database_backup' => $backup->database_backup_path,
                        'files_backup' => $backup->files_backup_path,
                        'total_size' => $backup->total_size,
                    ],
                ]);
            }

            // Step 4: Download update
            $systemUpdate->updateProgress(25, 'Downloading update package');
            $systemUpdate->markAsStarted();
            $downloadedFile = $this->downloadUpdate($versionCheck, $systemUpdate);

            // Step 5: Verify checksum
            $systemUpdate->updateProgress(40, 'Verifying package integrity');
            $this->verifyChecksum($downloadedFile, $systemUpdate->checksum);

            // Step 6: Extract update
            $systemUpdate->updateProgress(50, 'Extracting update files');
            $systemUpdate->update(['status' => 'installing']);
            $extractPath = $this->extractUpdate($downloadedFile);

            // Step 7: Apply file updates
            $systemUpdate->updateProgress(60, 'Applying file updates');
            $this->applyFileUpdates($extractPath);

            // Step 8: Run database migrations
            $systemUpdate->updateProgress(75, 'Running database migrations');
            $this->runMigrations();

            // Step 9: Run post-update commands
            $systemUpdate->updateProgress(85, 'Running post-update commands');
            $this->runPostUpdateCommands();

            // Step 10: Update version in config
            $systemUpdate->updateProgress(95, 'Updating version configuration');
            $this->updateVersionConfig($versionCheck->latest_version);

            // Step 11: Disable maintenance mode
            if (config('updater.maintenance_mode', true)) {
                $systemUpdate->updateProgress(98, 'Disabling maintenance mode');
                $this->disableMaintenanceMode();
            }

            // Step 12: Complete
            $systemUpdate->markAsCompleted();
            $systemUpdate->updateProgress(100, 'Update completed successfully');

            // Notify admins of successful update
            $this->notifyUpdateCompleted($systemUpdate);

            // Cleanup
            $this->cleanup($downloadedFile, $extractPath);

            Log::info('Update completed successfully', ['version' => $versionCheck->latest_version]);

            return $systemUpdate;

        } catch (\Exception $e) {
            Log::error('Update failed', [
                'version' => $versionCheck->latest_version,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $systemUpdate->markAsFailed($e->getMessage());

            // Notify admins of failed update
            $this->notifyUpdateFailed($systemUpdate, $e);

            // Attempt rollback if backup exists
            if ($systemUpdate->backup_info) {
                try {
                    $this->rollbackUpdate($systemUpdate);
                } catch (\Exception $rollbackException) {
                    Log::error('Rollback also failed', [
                        'error' => $rollbackException->getMessage(),
                    ]);
                }
            }

            // Disable maintenance mode if it was enabled
            if (config('updater.maintenance_mode', true) && app()->isDownForMaintenance()) {
                $this->disableMaintenanceMode();
            }

            throw $e;
        }
    }

    /**
     * Download update package
     */
    protected function downloadUpdate(VersionCheck $versionCheck, SystemUpdate $systemUpdate): string
    {
        $url = $versionCheck->download_url;

        if (!$url) {
            throw new \Exception('No download URL available for this version');
        }

        $filename = 'update_' . $versionCheck->latest_version . '_' . time() . '.zip';
        $filepath = $this->downloadPath . '/' . $filename;

        Log::info('Downloading update', ['url' => $url, 'filepath' => $filepath]);

        try {
            $response = Http::timeout(300)
                ->withOptions(['sink' => $filepath])
                ->withHeaders($this->getDownloadHeaders())
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Failed to download update: HTTP ' . $response->status());
            }

            if (!File::exists($filepath)) {
                throw new \Exception('Downloaded file does not exist');
            }

            $size = File::size($filepath);
            Log::info('Update downloaded', ['size' => $size, 'filepath' => $filepath]);

            return $filepath;

        } catch (\Exception $e) {
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
            throw new \Exception('Failed to download update: ' . $e->getMessage());
        }
    }

    /**
     * Verify checksum of downloaded file
     */
    protected function verifyChecksum(string $filepath, ?string $expectedChecksum): bool
    {
        if (!$expectedChecksum) {
            Log::warning('No checksum provided, skipping verification');
            return true;
        }

        if (!File::exists($filepath)) {
            throw new \Exception('File does not exist for checksum verification');
        }

        $actualChecksum = hash_file('sha256', $filepath);

        if ($actualChecksum !== $expectedChecksum) {
            throw new \Exception('Checksum verification failed. Expected: ' . $expectedChecksum . ', Got: ' . $actualChecksum);
        }

        Log::info('Checksum verified successfully');
        return true;
    }

    /**
     * Extract update package
     */
    protected function extractUpdate(string $filepath): string
    {
        $extractPath = $this->tempPath . '/update_' . time();

        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }

        Log::info('Extracting update', ['filepath' => $filepath, 'extract_to' => $extractPath]);

        $zip = new ZipArchive();

        if ($zip->open($filepath) !== TRUE) {
            throw new \Exception('Could not open zip file for extraction');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // GitHub releases often have a root directory - find it
        $files = File::directories($extractPath);
        if (count($files) === 1) {
            // Single root directory - use it
            $extractPath = $files[0];
        }

        Log::info('Update extracted successfully', ['path' => $extractPath]);

        return $extractPath;
    }

    /**
     * Apply file updates
     */
    protected function applyFileUpdates(string $extractPath): void
    {
        $basePath = base_path();
        $excludedPaths = config('updater.excluded_paths', []);

        Log::info('Applying file updates', [
            'source' => $extractPath,
            'destination' => $basePath,
            'excluded' => $excludedPaths,
        ]);

        // Get all files from extract path
        $files = $this->getFilesRecursively($extractPath);

        foreach ($files as $file) {
            $relativePath = str_replace($extractPath . '/', '', $file);

            // Check if file should be excluded
            $shouldExclude = false;
            foreach ($excludedPaths as $excludedPath) {
                if (str_starts_with($relativePath, $excludedPath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $destinationPath = $basePath . '/' . $relativePath;

            // Create directory if it doesn't exist
            $destinationDir = dirname($destinationPath);
            if (!File::exists($destinationDir)) {
                File::makeDirectory($destinationDir, 0755, true);
            }

            // Copy file
            File::copy($file, $destinationPath);
        }

        Log::info('File updates applied successfully', ['files_copied' => count($files)]);
    }

    /**
     * Run database migrations
     */
    protected function runMigrations(): void
    {
        Log::info('Running database migrations');

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Log::info('Migrations completed', ['output' => $output]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to run migrations: ' . $e->getMessage());
        }
    }

    /**
     * Run post-update commands
     */
    protected function runPostUpdateCommands(): void
    {
        $commands = config('updater.post_update_commands', []);

        if (empty($commands)) {
            Log::info('No post-update commands to run');
            return;
        }

        Log::info('Running post-update commands', ['commands' => $commands]);

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $output = Artisan::output();

                Log::info('Command executed', ['command' => $command, 'output' => $output]);

            } catch (\Exception $e) {
                Log::warning('Post-update command failed (non-fatal)', [
                    'command' => $command,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Update version in config
     */
    protected function updateVersionConfig(string $newVersion): void
    {
        $configPath = config_path('updater.php');

        if (!File::exists($configPath)) {
            Log::warning('Config file not found, skipping version update in config');
            return;
        }

        $content = File::get($configPath);

        // Replace version string
        $content = preg_replace(
            "/'current_version'\s*=>\s*'[^']+'/",
            "'current_version' => '{$newVersion}'",
            $content
        );

        File::put($configPath, $content);

        Log::info('Version updated in config', ['new_version' => $newVersion]);
    }

    /**
     * Rollback to previous version
     */
    public function rollbackUpdate(SystemUpdate $systemUpdate): bool
    {
        Log::info('Starting rollback', ['update_id' => $systemUpdate->id]);

        if (!$systemUpdate->canRollback()) {
            throw new \Exception('Cannot rollback: No restorable backup available');
        }

        try {
            // Enable maintenance mode
            if (config('updater.maintenance_mode', true)) {
                $this->enableMaintenanceMode();
            }

            // Get backup
            $backupId = $systemUpdate->backup_info['backup_id'] ?? null;
            if (!$backupId) {
                throw new \Exception('No backup ID found in update info');
            }

            $backup = UpdateBackup::find($backupId);
            if (!$backup) {
                throw new \Exception('Backup not found');
            }

            // Restore backup
            $this->backupService->restoreBackup($backup);

            // Update system update status
            $systemUpdate->update(['status' => 'rolled_back']);

            // Disable maintenance mode
            if (config('updater.maintenance_mode', true)) {
                $this->disableMaintenanceMode();
            }

            Log::info('Rollback completed successfully');

            return true;

        } catch (\Exception $e) {
            Log::error('Rollback failed', ['error' => $e->getMessage()]);

            // Try to disable maintenance mode
            if (config('updater.maintenance_mode', true) && app()->isDownForMaintenance()) {
                $this->disableMaintenanceMode();
            }

            throw $e;
        }
    }

    /**
     * Run pre-update checks
     */
    protected function runPreUpdateChecks(): void
    {
        Log::info('Running pre-update checks');

        // Check PHP version
        $minPhpVersion = config('updater.min_php_version', '8.2.0');
        if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
            throw new \Exception("PHP version {$minPhpVersion} or higher is required. Current: " . PHP_VERSION);
        }

        // Check required extensions
        $requiredExtensions = config('updater.required_extensions', []);
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                throw new \Exception("Required PHP extension not loaded: {$extension}");
            }
        }

        // Check disk space
        $downloadPath = config('updater.download_path', storage_path('app/updates'));
        $freeSpace = disk_free_space(dirname($downloadPath));
        $requiredSpace = 500 * 1024 * 1024; // 500MB minimum

        if ($freeSpace < $requiredSpace) {
            throw new \Exception("Insufficient disk space. Required: 500MB, Available: " . round($freeSpace / 1024 / 1024) . "MB");
        }

        // Check write permissions
        $pathsToCheck = [
            base_path(),
            storage_path(),
            config_path(),
        ];

        foreach ($pathsToCheck as $path) {
            if (!is_writable($path)) {
                throw new \Exception("Path is not writable: {$path}");
            }
        }

        Log::info('Pre-update checks passed');
    }

    /**
     * Enable maintenance mode
     */
    protected function enableMaintenanceMode(): void
    {
        if (app()->isDownForMaintenance()) {
            Log::info('Maintenance mode already enabled');
            return;
        }

        Artisan::call('down', ['--render' => 'errors::503']);
        Log::info('Maintenance mode enabled');
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        if (!app()->isDownForMaintenance()) {
            Log::info('Maintenance mode already disabled');
            return;
        }

        Artisan::call('up');
        Log::info('Maintenance mode disabled');
    }

    /**
     * Cleanup temporary files
     */
    protected function cleanup(string $downloadedFile, string $extractPath): void
    {
        Log::info('Cleaning up temporary files', [
            'downloaded_file' => $downloadedFile,
            'extract_path' => $extractPath,
        ]);

        // Delete downloaded file
        if (File::exists($downloadedFile)) {
            File::delete($downloadedFile);
        }

        // Delete extracted files
        if (File::exists($extractPath)) {
            File::deleteDirectory($extractPath);
        }

        Log::info('Cleanup completed');
    }

    /**
     * Notify admins of completed update
     */
    protected function notifyUpdateCompleted(SystemUpdate $systemUpdate): void
    {
        if (!config('updater.notifications.enabled', true)) {
            return;
        }

        $message = "Update to version {$systemUpdate->version} completed successfully!";

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            UpdateNotification::create([
                'version' => $systemUpdate->version,
                'notification_type' => 'update_completed',
                'message' => $message,
                'metadata' => [
                    'system_update_id' => $systemUpdate->id,
                    'previous_version' => $systemUpdate->previous_version,
                    'duration' => $systemUpdate->started_at->diffInSeconds($systemUpdate->completed_at),
                ],
                'user_id' => $admin->id,
            ]);
        }

        Log::info('Update completion notifications sent', ['admin_count' => $admins->count()]);
    }

    /**
     * Notify admins of failed update
     */
    protected function notifyUpdateFailed(SystemUpdate $systemUpdate, \Exception $exception): void
    {
        if (!config('updater.notifications.enabled', true)) {
            return;
        }

        $message = "Update to version {$systemUpdate->version} failed. Error: {$exception->getMessage()}";

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            UpdateNotification::create([
                'version' => $systemUpdate->version,
                'notification_type' => 'update_failed',
                'message' => $message,
                'metadata' => [
                    'system_update_id' => $systemUpdate->id,
                    'error' => $exception->getMessage(),
                    'error_trace' => $exception->getTraceAsString(),
                ],
                'user_id' => $admin->id,
            ]);
        }

        Log::info('Update failure notifications sent', ['admin_count' => $admins->count()]);
    }

    /**
     * Determine update type based on version numbers
     */
    protected function determineUpdateType(string $currentVersion, string $newVersion): string
    {
        $current = explode('.', $currentVersion);
        $new = explode('.', $newVersion);

        if (($current[0] ?? 0) < ($new[0] ?? 0)) {
            return 'major';
        }

        if (($current[1] ?? 0) < ($new[1] ?? 0)) {
            return 'minor';
        }

        if (($current[2] ?? 0) < ($new[2] ?? 0)) {
            return 'patch';
        }

        return 'hotfix';
    }

    /**
     * Get download headers
     */
    protected function getDownloadHeaders(): array
    {
        $headers = [
            'Accept' => 'application/octet-stream',
        ];

        $token = config('updater.server_token');
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    /**
     * Get all files recursively
     */
    protected function getFilesRecursively(string $path): array
    {
        $files = [];

        foreach (File::allFiles($path) as $file) {
            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * Get latest system update
     */
    public function getLatestUpdate(): ?SystemUpdate
    {
        return SystemUpdate::orderBy('created_at', 'desc')->first();
    }

    /**
     * Check if update is in progress
     */
    public function isUpdateInProgress(): bool
    {
        return SystemUpdate::whereIn('status', ['pending', 'downloading', 'installing'])
            ->exists();
    }
}
