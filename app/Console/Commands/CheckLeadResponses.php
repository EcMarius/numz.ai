<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Wave\Plugins\EvenLeads\Models\Lead;
use App\Services\ResponseTrackerService;
use Illuminate\Support\Facades\Log;

class CheckLeadResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:check-responses
                            {--limit=50 : Maximum number of leads to check per run}
                            {--days=30 : Only check leads contacted within this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check contacted leads for responses (runs every 6 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting lead response check...');

        $limit = (int) $this->option('limit');
        $days = (int) $this->option('days');

        // Get leads that need response checking
        // - Contacted within last X days
        // - No response received yet
        // - Last checked > 6 hours ago (or never checked)
        $leads = Lead::needsResponseCheck()
            ->where('last_contact_at', '>=', now()->subDays($days))
            ->limit($limit)
            ->get();

        $this->info("Found {$leads->count()} leads to check");

        if ($leads->isEmpty()) {
            $this->info('No leads to check. Exiting.');
            return 0;
        }

        $trackerService = app(ResponseTrackerService::class);

        // Batch check responses (rate limit optimized)
        $newResponsesCount = $trackerService->batchCheckResponses($leads);

        $this->info("Response check complete:");
        $this->info("  - Leads checked: {$leads->count()}");
        $this->info("  - New responses detected: {$newResponsesCount}");

        Log::info('Lead response check completed', [
            'leads_checked' => $leads->count(),
            'new_responses' => $newResponsesCount,
            'limit' => $limit,
            'days_window' => $days,
        ]);

        return 0;
    }
}
