<?php

namespace App\Console\Commands;

use App\Numz\Services\VersionCheckerService;
use Illuminate\Console\Command;

class UpdateCheckCommand extends Command
{
    protected $signature = 'update:check
                            {--force : Force a fresh check, ignoring cache}';

    protected $description = 'Check for available system updates';

    public function handle(VersionCheckerService $versionChecker): int
    {
        $this->info('Checking for system updates...');
        $this->newLine();

        try {
            $force = $this->option('force');
            $versionCheck = $versionChecker->checkForUpdates($force);

            // Display current version
            $this->line('<fg=cyan>Current Version:</> ' . $versionCheck->current_version);
            $this->line('<fg=cyan>Latest Version:</>  ' . $versionCheck->latest_version);
            $this->newLine();

            if ($versionCheck->update_available) {
                $this->components->success('Update available!');
                $this->newLine();

                // Display changelog if available
                if ($versionCheck->changelog) {
                    $this->line('<fg=yellow>Changelog:</>');
                    $this->line('─────────────────────────────────────────────────────');
                    $this->line($versionCheck->changelog);
                    $this->line('─────────────────────────────────────────────────────');
                    $this->newLine();
                }

                // Display download info
                if ($versionCheck->download_url) {
                    $this->line('<fg=cyan>Download URL:</> ' . $versionCheck->download_url);
                }

                if ($versionCheck->release_info['size'] ?? null) {
                    $size = $this->formatBytes($versionCheck->release_info['size']);
                    $this->line('<fg=cyan>Download Size:</> ' . $size);
                }

                if ($versionCheck->release_info['checksum'] ?? null) {
                    $this->line('<fg=cyan>Checksum:</> ' . $versionCheck->release_info['checksum']);
                }

                $this->newLine();
                $this->line('To apply this update, run: <fg=green>php artisan update:apply</>');

                return Command::SUCCESS;

            } else {
                $this->components->info('Your system is up to date!');
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->components->error('Failed to check for updates: ' . $e->getMessage());
            return Command::FAILURE;
        }
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
