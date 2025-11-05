<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plugins\EvenLeads\Models\SyncHistory;
use Carbon\Carbon;

class CleanupStuckSyncs extends Command
{
    protected $signature = 'evenleads:cleanup-stuck-syncs';
    protected $description = 'Mark syncs stuck in running status as failed';

    public function handle()
    {
        // Find syncs that have been "running" for more than 10 minutes
        $stuckSyncs = SyncHistory::where('status', 'running')
            ->where('started_at', '<', Carbon::now()->subMinutes(10))
            ->whereNull('completed_at')
            ->get();

        $count = $stuckSyncs->count();

        if ($count === 0) {
            $this->info('No stuck syncs found.');
            return 0;
        }

        $this->info("Found {$count} stuck sync(s). Marking as failed...");

        foreach ($stuckSyncs as $sync) {
            $sync->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'Sync timed out or crashed (auto-cleanup)',
            ]);

            $this->line(" - Cleaned up sync #{$sync->id} (Campaign #{$sync->campaign_id})");
        }

        $this->info("Successfully cleaned up {$count} stuck sync(s).");

        return 0;
    }
}
