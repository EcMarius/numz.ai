<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plugins\EvenLeads\Services\SyncLogger;

class CleanupSyncLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evenleads:cleanup-sync-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up sync debug logs older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up old sync debug logs...');

        try {
            $deleted = SyncLogger::cleanupOldLogs();

            if ($deleted > 0) {
                $this->info("✓ Cleaned up {$deleted} old log file(s).");
            } else {
                $this->info('✓ No old log files to clean up.');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clean up logs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
