<?php

namespace App\Console\Commands;

use App\Models\SystemUpdate;
use App\Numz\Services\UpdateService;
use Illuminate\Console\Command;

class UpdateRollbackCommand extends Command
{
    protected $signature = 'update:rollback
                            {id? : Specific update ID to rollback}
                            {--force : Skip confirmation}';

    protected $description = 'Rollback a system update to the previous version';

    public function handle(UpdateService $updateService): int
    {
        $this->info('System Update Rollback');
        $this->newLine();

        try {
            // Get update to rollback
            $updateId = $this->argument('id');

            if ($updateId) {
                $systemUpdate = SystemUpdate::find($updateId);

                if (!$systemUpdate) {
                    $this->components->error("Update with ID {$updateId} not found.");
                    return Command::FAILURE;
                }

            } else {
                // Get latest completed update
                $systemUpdate = SystemUpdate::where('status', 'completed')
                    ->orderBy('completed_at', 'desc')
                    ->first();

                if (!$systemUpdate) {
                    $this->components->error('No completed updates found to rollback.');
                    return Command::FAILURE;
                }
            }

            // Check if rollback is possible
            if (!$systemUpdate->canRollback()) {
                $this->components->error('This update cannot be rolled back.');
                $this->line('Reasons:');
                $this->line('  • Update status is not "completed", or');
                $this->line('  • No restorable backup is available');
                return Command::FAILURE;
            }

            // Display rollback information
            $this->displayRollbackInfo($systemUpdate);

            // Confirm rollback
            if (!$this->option('force')) {
                if (!$this->confirm('Do you want to proceed with this rollback?', false)) {
                    $this->components->warn('Rollback cancelled.');
                    return Command::SUCCESS;
                }
            }

            // Perform rollback
            $this->newLine();
            $this->line('Starting rollback...');
            $this->newLine();

            $updateService->rollbackUpdate($systemUpdate);

            // Display success message
            $this->newLine();
            $this->components->success('Rollback completed successfully!');
            $this->line('System has been restored to version ' . $systemUpdate->previous_version);
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->components->error('Rollback failed: ' . $e->getMessage());
            $this->newLine();

            if ($this->option('verbose')) {
                $this->line('<fg=red>Stack Trace:</>');
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    protected function displayRollbackInfo(SystemUpdate $systemUpdate): void
    {
        $this->newLine();
        $this->line('<fg=yellow>Rollback Information:</>');
        $this->line('═══════════════════════════════════════════');
        $this->line('<fg=cyan>Update ID:</>        ' . $systemUpdate->id);
        $this->line('<fg=cyan>Current Version:</>  ' . $systemUpdate->version);
        $this->line('<fg=cyan>Restore Version:</>  ' . $systemUpdate->previous_version);
        $this->line('<fg=cyan>Update Date:</>      ' . $systemUpdate->completed_at->format('M d, Y g:i A'));

        if ($systemUpdate->backup_info) {
            $this->line('<fg=cyan>Backup ID:</>        ' . ($systemUpdate->backup_info['backup_id'] ?? 'N/A'));

            if ($systemUpdate->backup_info['total_size'] ?? null) {
                $size = $this->formatBytes($systemUpdate->backup_info['total_size']);
                $this->line('<fg=cyan>Backup Size:</>      ' . $size);
            }
        }

        $this->line('═══════════════════════════════════════════');
        $this->newLine();

        $this->components->warn('WARNING: This will:');
        $this->line('  • Restore database to previous state');
        $this->line('  • Restore application files to previous version');
        $this->line('  • Any data created after the update may be lost');
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
