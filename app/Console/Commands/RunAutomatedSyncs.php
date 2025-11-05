<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plugins\EvenLeads\Models\Campaign;
use Wave\Plugins\EvenLeads\Jobs\SyncCampaignJob;
use Wave\Plugins\EvenLeads\Services\PlanLimitService;

class RunAutomatedSyncs extends Command
{
    protected $signature = 'evenleads:run-automated-syncs';
    protected $description = 'Run automated syncs for campaigns based on their sync schedules';

    public function handle()
    {
        $this->info('Checking for campaigns due for automated sync...');

        // Find campaigns that are due for sync
        $campaigns = Campaign::where('status', 'active')
            ->where(function($query) {
                $query->where('next_sync_at', '<=', now())
                      ->orWhereNull('next_sync_at'); // Include campaigns never synced
            })
            ->get();

        $this->info("Found {$campaigns->count()} campaign(s) due for sync");

        $syncedCount = 0;
        $skippedCount = 0;

        foreach ($campaigns as $campaign) {
            // Check if user's plan allows automated syncing
            $planLimitService = app(PlanLimitService::class);
            $limits = $planLimitService->getUserPlanLimits($campaign->user);

            $autoSyncInterval = $limits['automated_sync_interval_minutes'] ?? 0;

            if ($autoSyncInterval <= 0) {
                $this->warn("  Skipping campaign {$campaign->id} ({$campaign->name}) - plan doesn't allow automated sync");
                $skippedCount++;
                continue;
            }

            // Check if campaign has connected platforms
            $hasConnections = false;
            foreach ($campaign->platforms ?? [] as $platform) {
                if ($platform === 'reddit') {
                    $redditService = app(\Wave\Plugins\EvenLeads\Services\RedditService::class);
                    if ($redditService->isConnected($campaign->user_id)) {
                        $hasConnections = true;
                        break;
                    }
                }
            }

            if (!$hasConnections) {
                $this->warn("  Skipping campaign {$campaign->id} ({$campaign->name}) - no platforms connected");
                $skippedCount++;
                continue;
            }

            // Check BYOAPI requirement if setting is enabled
            if (setting('site.bring_your_api_key_required', false)) {
                $user = $campaign->user;
                $platforms = $campaign->platforms ?? [];
                $missingApiKeys = [];

                if (in_array('reddit', $platforms) && !$user->reddit_use_custom_api) {
                    $missingApiKeys[] = 'Reddit';
                }
                if (in_array('x', $platforms) && !$user->x_use_custom_api) {
                    $missingApiKeys[] = 'X';
                }

                if (!empty($missingApiKeys)) {
                    $this->warn("  Skipping campaign {$campaign->id} ({$campaign->name}) - Missing API keys for: " . implode(', ', $missingApiKeys));
                    $skippedCount++;
                    continue;
                }
            }

            // Dispatch sync job (isManual = false for automated sync, use intelligent mode)
            SyncCampaignJob::dispatch($campaign, false, 'intelligent');

            $this->info("  ✓ Dispatched sync for campaign {$campaign->id} ({$campaign->name})");
            $syncedCount++;
        }

        $this->newLine();
        $this->info("Automated sync complete:");
        $this->info("  - Synced: {$syncedCount}");
        $this->info("  - Skipped: {$skippedCount}");

        return 0;
    }
}
