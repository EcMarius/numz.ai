<?php

namespace App\Console\Commands;

use App\Models\UpdateBackup;
use App\Numz\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestoreCommand extends Command
{
    protected $signature = 'backup:restore
                            {id : The backup ID to restore}
                            {--force : Skip confirmation}';

    protected $description = 'Restore a system backup';

    public function handle(BackupService $backupService): int
    {
        $this->info('Restore System Backup');
        $this->newLine();

        try {
            $backupId = $this->argument('id');

            // Find backup
            $backup = UpdateBackup::find($backupId);

            if (!$backup) {
                $this->components->error("Backup with ID {$backupId} not found.");
                return Command::FAILURE;
            }

            // Check if backup is restorable
            if (!$backup->is_restorable) {
                $this->components->error('This backup is marked as not restorable.');
                return Command::FAILURE;
            }

            // Check if backup files exist
            if (!$backup->filesExist()) {
                $this->components->error('Backup files are missing or corrupted.');
                return Command::FAILURE;
            }

            // Display backup information
            $this->displayBackupInfo($backup);

            // Confirm restore
            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to proceed with this restore?', false)) {
                    $this->components->warn('Restore cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Perform restore
            $this->newLine();
            $this->line('Starting restore process...');
            $this->line('This may take several minutes...');
            $this->newLine();

            $backupService->restoreBackup($backup);

            // Display success message
            $this->newLine();
            $this->components->success('Backup restored successfully!');
            $this->line('System has been restored to version ' . $backup->version);
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Restore failed: ' . $e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line('<fg=red>Stack Trace:</>');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    protected function displayBackupInfo(UpdateBackup $backup): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Backup Information:</>');
        $this->line('═══════════════════════════════════════════');
        $this->line('<fg=cyan>Backup ID:</>       ' . $backup->id);
        $this->line('<fg=cyan>Version:</>         ' . $backup->version);
        $this->line('<fg=cyan>Total Size:</>      ' . $this->formatBytes($backup->total_size));
        $this->line('<fg=cyan>Created At:</>      ' . $backup->created_at->format('M d, Y g:i A'));

        if ($backup->database_backup_path) {
            $this->line('<fg=cyan>Database Backup:</> ' . $backup->database_backup_path);
        }

        if ($backup->files_backup_path) {
            $this->line('<fg=cyan>Files Backup:</>    ' . $backup->files_backup_path);
        }

        $this->line('═══════════════════════════════════════════');
        $this->newLine();

        $this->components->warn('WARNING: This will:');
        $this->line('  • Replace current database with backup');
        $this->line('  • Replace current application files with backup');
        $this->line('  • Any data created after the backup may be lost');
        $this->line('  • System will be put in maintenance mode during restore');
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
