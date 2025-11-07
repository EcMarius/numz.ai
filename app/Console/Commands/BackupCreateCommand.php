<?php

namespace App\Console\Commands;

use App\Models\SystemUpdate;
use App\Numz\Services\BackupService;
use Illuminate\Console\Command;

class BackupCreateCommand extends Command
{
    protected $signature = 'backup:create
                            {--description= : Optional description for the backup}';

    protected $description = 'Create a manual system backup';

    public function handle(BackupService $backupService): int
    {
        $this->info('Creating System Backup');
        $this->newLine();

        try {
            $this->line('Starting backup process...');

            // Create a dummy system update for manual backup
            $systemUpdate = SystemUpdate::create([
                'version' => config('updater.current_version'),
                'previous_version' => config('updater.current_version'),
                'update_type' => 'manual_backup',
                'status' => 'completed',
                'initiated_by' => null,
                'changelog' => $this->option('description') ?? 'Manual backup created via CLI',
            ]);

            $backup = $backupService->createBackup($systemUpdate);

            $this->newLine();
            $this->components->success('Backup created successfully!');
            $this->newLine();

            // Display backup information
            $this->line('<fg=cyan>Backup Information:</>');
            $this->line('═══════════════════════════════════════════');
            $this->line('<fg=cyan>Backup ID:</>       ' . $backup->id);
            $this->line('<fg=cyan>Version:</>         ' . $backup->version);
            $this->line('<fg=cyan>Total Size:</>      ' . $this->formatBytes($backup->total_size));
            $this->line('<fg=cyan>Database Backup:</> ' . ($backup->database_backup_path ?: 'N/A'));
            $this->line('<fg=cyan>Files Backup:</>    ' . ($backup->files_backup_path ?: 'N/A'));
            $this->line('<fg=cyan>Is Restorable:</>   ' . ($backup->is_restorable ? 'Yes' : 'No'));
            $this->line('<fg=cyan>Created At:</>      ' . $backup->created_at->format('M d, Y g:i A'));
            $this->line('═══════════════════════════════════════════');
            $this->newLine();

            $this->line('To restore this backup, run: <fg=green>php artisan backup:restore ' . $backup->id . '</>');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Backup failed: ' . $e->getMessage());
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
