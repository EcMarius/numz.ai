<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadMessage;
use App\Services\FollowUpService;
use Illuminate\Support\Facades\Log;

class SendScheduledFollowUps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:send-follow-ups
                            {--limit=20 : Maximum number of follow-ups to send per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled follow-up messages to leads (runs hourly)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled follow-up processing...');

        // Check if follow-up system is enabled
        $followUpSystemEnabled = setting('site.follow_up_system_enabled', true);

        if (!$followUpSystemEnabled) {
            $this->warn('⚠ Follow-up system is DISABLED by admin. No follow-ups will be sent.');
            $this->info('To enable, toggle the switch in Admin > System Controls');
            Log::info('Follow-up processing skipped - system disabled by admin');
            return 0;
        }

        $limit = (int) $this->option('limit');

        // Get follow-up messages that are ready to send
        $followUps = LeadMessage::where('is_follow_up', true)
            ->where('status', 'draft')
            ->whereNotNull('scheduled_send_at')
            ->where('scheduled_send_at', '<=', now())
            ->with(['lead', 'lead.user'])
            ->limit($limit)
            ->get();

        $this->info("Found {$followUps->count()} follow-ups ready to send");

        if ($followUps->isEmpty()) {
            $this->info('No follow-ups to send. Exiting.');
            return 0;
        }

        $followUpService = app(FollowUpService::class);

        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // Group by platform to respect rate limits (max 5 per platform per hour)
        $followUpsByPlatform = $followUps->groupBy('lead.platform');

        foreach ($followUpsByPlatform as $platform => $platformFollowUps) {
            // Limit to 5 follow-ups per platform to respect rate limits
            $platformFollowUps = $platformFollowUps->take(5);

            foreach ($platformFollowUps as $followUp) {
                $this->info("Processing follow-up {$followUp->id} for lead {$followUp->lead_id}");

                $success = $followUpService->sendFollowUp($followUp);

                if ($success) {
                    $sentCount++;
                    $this->info("  ✓ Sent successfully");
                } elseif ($followUp->status === 'cancelled') {
                    $skippedCount++;
                    $this->info("  ⊘ Skipped (lead already responded)");
                } else {
                    $failedCount++;
                    $this->error("  ✗ Failed to send");
                }

                // Small delay between sends (200ms) to avoid rate limiting
                usleep(200000);
            }

            // If we processed the max for this platform, log it
            if ($platformFollowUps->count() === 5 && $followUpsByPlatform[$platform]->count() > 5) {
                $this->warn("Rate limit: Only processed 5 follow-ups for {$platform}. Remaining will be processed in next run.");
            }
        }

        $this->info("\nFollow-up processing complete:");
        $this->info("  - Sent: {$sentCount}");
        $this->info("  - Skipped: {$skippedCount}");
        $this->info("  - Failed: {$failedCount}");

        Log::info('Scheduled follow-ups processed', [
            'sent' => $sentCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
            'limit' => $limit,
        ]);

        return 0;
    }
}
