<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiUsageTracker;

class CleanupApiUsageLogs extends Command
{
    protected $signature = 'api-usage:cleanup {--days=90 : Number of days to keep}';
    protected $description = 'Clean up old API usage logs';

    public function handle()
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up API usage logs older than {$days} days...");

        try {
            $deleted = ApiUsageTracker::cleanupOldLogs($days);

            $this->info("Successfully deleted {$deleted} old log entries");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error cleaning up logs: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
