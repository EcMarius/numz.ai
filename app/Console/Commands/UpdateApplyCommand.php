<?php

namespace App\Console\Commands;

use App\Models\VersionCheck;
use App\Numz\Services\UpdateService;
use App\Numz\Services\VersionCheckerService;
use Illuminate\Console\Command;

class UpdateApplyCommand extends Command
{
    protected $signature = 'update:apply
                            {version? : Specific version to apply (defaults to latest)}
                            {--no-backup : Skip creating a backup}
                            {--no-maintenance : Skip enabling maintenance mode}
                            {--force : Skip confirmation}';

    protected $description = 'Apply a system update';

    public function handle(UpdateService $updateService, VersionCheckerService $versionChecker): int
    {
        $this->info('System Update');
        $this->newLine();

        try {
            // Check if update is already in progress
            if ($updateService->isUpdateInProgress()) {
                $this->components->error('An update is already in progress!');
                return Command::FAILURE;
            }

            // Get version to update to
            $versionToUpdate = $this->argument('version');

            if ($versionToUpdate) {
                // Find specific version check
                $versionCheck = VersionCheck::where('latest_version', $versionToUpdate)
                    ->orderBy('checked_at', 'desc')
                    ->first();

                if (!$versionCheck) {
                    $this->components->error("Version {$versionToUpdate} not found in version checks.");
                    $this->line('Run <fg=green>php artisan update:check</> first to check for this version.');
                    return Command::FAILURE;
                }

            } else {
                // Get latest available update
                $this->line('Checking for latest update...');
                $versionCheck = $versionChecker->checkForUpdates(force: true);

                if (!$versionCheck->update_available) {
                    $this->components->info('Your system is already up to date!');
                    return Command::SUCCESS;
                }
            }

            // Display update information
            $this->displayUpdateInfo($versionCheck);

            // Confirm update
            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to proceed with this update?', false)) {
                    $this->components->warn('Update cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Override config based on options
            if ($this->option('no-backup')) {
                config(['updater.backup_before_update' => false]);
                $this->components->warn('Backup will be skipped as requested.');
            }

            if ($this->option('no-maintenance')) {
                config(['updater.maintenance_mode' => false]);
                $this->components->warn('Maintenance mode will not be enabled.');
            }

            // Start update
            $this->newLine();
            $this->line('Starting update...');
            $this->newLine();

            $systemUpdate = $updateService->applyUpdate($versionCheck, null);

            // Display success message
            $this->newLine();
            $this->components->success('Update completed successfully!');
            $this->line('Updated from version ' . $systemUpdate->previous_version . ' to ' . $systemUpdate->version);
            $this->newLine();

            // Display backup info if created
            if ($systemUpdate->backup_info) {
                $this->line('<fg=cyan>Backup Information:</>');
                $this->line('─────────────────────────────────────────');
                $this->line('Backup ID: ' . ($systemUpdate->backup_info['backup_id'] ?? 'N/A'));

                if ($systemUpdate->backup_info['total_size'] ?? null) {
                    $size = $this->formatBytes($systemUpdate->backup_info['total_size']);
                    $this->line('Backup Size: ' . $size);
                }

                $this->line('─────────────────────────────────────────');
                $this->newLine();
                $this->line('To rollback this update, run: <fg=yellow>php artisan update:rollback</>');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Update failed: ' . $e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line('<fg=red>Stack Trace:</>');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    protected function displayUpdateInfo(VersionCheck $versionCheck): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Update Information:</>');
        $this->line('═══════════════════════════════════════════');
        $this->line('<fg=cyan>Current Version:</> ' . $versionCheck->current_version);
        $this->line('<fg=cyan>Target Version:</>  ' . $versionCheck->latest_version);

        if ($versionCheck->release_info['size'] ?? null) {
            $size = $this->formatBytes($versionCheck->release_info['size']);
            $this->line('<fg=cyan>Download Size:</>   ' . $size);
        }

        $this->line('═══════════════════════════════════════════');

        if ($versionCheck->changelog) {
            $this->newLine();
            $this->line('<fg=yellow>Changelog:</>');
            $this->line('─────────────────────────────────────────');
            $this->line($versionCheck->changelog);
            $this->line('─────────────────────────────────────────');
        }

        $this->newLine();
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}
