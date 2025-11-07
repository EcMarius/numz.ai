<?php

namespace App\Console\Commands;

use App\Models\UpdateBackup;
use Illuminate\Console\Command;

class BackupCleanupCommand extends Command
{
    protected $signature = 'backup:cleanup
                            {--keep= : Number of backups to keep (overrides config)}
                            {--force : Skip confirmation}';

    protected $description = 'Clean up old system backups based on retention policy';

    public function handle(): int
    {
        $this->info('Backup Cleanup');
        $this->newLine();

        try {
            // Get retention count
            $retention = $this->option('keep')
                ?? config('updater.backup_retention', 3);

            $allBackups = UpdateBackup::orderBy('created_at', 'desc')->get();
            $totalBackups = $allBackups->count();

            if ($totalBackups <= $retention) {
                $this->components->info('No backups need to be cleaned up.');
                $this->line("Current backups: {$totalBackups}, Retention policy: {$retention}");
                return Command::SUCCESS;
            }

            $backupsToDelete = $allBackups->slice($retention);
            $deleteCount = $backupsToDelete->count();

            // Display cleanup information
            $this->line('<fg=yellow>Cleanup Information:</>');
            $this->line('═══════════════════════════════════════════');
            $this->line('<fg=cyan>Total Backups:</>        ' . $totalBackups);
            $this->line('<fg=cyan>Retention Policy:</>     Keep ' . $retention . ' most recent');
            $this->line('<fg=cyan>Backups to Delete:</>    ' . $deleteCount);
            $this->line('═══════════════════════════════════════════');
            $this->newLine();

            // List backups to delete
            if ($deleteCount > 0) {
                $this->line('<fg=yellow>Backups to be deleted:</>');
                $this->line('─────────────────────────────────────────');

                foreach ($backupsToDelete as $backup) {
                    $this->line(sprintf(
                        'ID: %d | Version: %s | Size: %s | Date: %s',
                        $backup->id,
                        $backup->version,
                        $this->formatBytes($backup->total_size),
                        $backup->created_at->format('M d, Y')
                    ));
                }

                $this->line('─────────────────────────────────────────');
                $this->newLine();
            }

            // Confirm deletion
            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to proceed with the cleanup?', false)) {
                    $this->components->warn('Cleanup cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Delete backups
            $deletedCount = 0;
            $failedCount = 0;
            $freedSpace = 0;

            foreach ($backupsToDelete as $backup) {
                try {
                    $freedSpace += $backup->total_size;
                    $backup->deleteFiles();
                    $backup->delete();
                    $deletedCount++;

                    if ($this->option('verbose')) {
                        $this->line('✓ Deleted backup ID ' . $backup->id);
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $this->components->warn('Failed to delete backup ID ' . $backup->id . ': ' . $e->getMessage());
                }
            }

            // Display results
            $this->newLine();
            $this->components->success('Cleanup completed!');
            $this->line('<fg=cyan>Deleted:</>     ' . $deletedCount . ' backup(s)');

            if ($failedCount > 0) {
                $this->line('<fg=red>Failed:</>      ' . $failedCount . ' backup(s)');
            }

            $this->line('<fg=cyan>Space Freed:</> ' . $this->formatBytes($freedSpace));
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Cleanup failed: ' . $e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line('<fg=red>Stack Trace:</>');
                $this->line($e->getTraceAsString());
            }

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
