<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Illuminate\Support\Facades\DB;

class StopAllSyncs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'evenleads:stop-all-syncs
                            {--force : Force stop without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop all currently running campaign syncs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find all campaigns currently syncing
        $syncingCampaigns = Campaign::where('status', 'syncing')->get();

        if ($syncingCampaigns->isEmpty()) {
            $this->info('✓ No campaigns are currently syncing.');
            return Command::SUCCESS;
        }

        $this->info("Found {$syncingCampaigns->count()} campaign(s) currently syncing:");
        foreach ($syncingCampaigns as $campaign) {
            $this->line("  - Campaign #{$campaign->id}: {$campaign->name}");
        }

        // Ask for confirmation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to stop all these syncs?', true)) {
                $this->info('Cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info('Stopping all syncs...');

        $stopped = 0;
        $failed = 0;

        foreach ($syncingCampaigns as $campaign) {
            try {
                // Mark campaign as active (stops the sync)
                $campaign->update(['status' => 'active']);

                $this->line("  ✓ Stopped sync for campaign #{$campaign->id}");
                $stopped++;
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to stop campaign #{$campaign->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        // Remove any pending sync jobs from queue
        $this->info('Removing pending sync jobs from queue...');
        $deletedJobs = DB::table('jobs')
            ->where('queue', 'default')
            ->where('payload', 'like', '%SyncCampaignJob%')
            ->delete();

        if ($deletedJobs > 0) {
            $this->line("  ✓ Removed {$deletedJobs} pending sync job(s) from queue");
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->line("  Campaigns stopped: {$stopped}");
        if ($failed > 0) {
            $this->line("  Failed: {$failed}");
        }
        if ($deletedJobs > 0) {
            $this->line("  Queue jobs removed: {$deletedJobs}");
        }

        $this->newLine();
        $this->info('✓ All syncs have been stopped.');

        return Command::SUCCESS;
    }
}
