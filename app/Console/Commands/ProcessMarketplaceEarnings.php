<?php

namespace App\Console\Commands;

use App\Models\Marketplace\MarketplaceEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMarketplaceEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:process-earnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process marketplace earnings and mark them as available for payout after holding period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing marketplace earnings...');

        // Get earnings that are pending and past their available_at date
        $earnings = MarketplaceEarning::where('status', 'pending')
            ->where('available_at', '<=', now())
            ->get();

        if ($earnings->isEmpty()) {
            $this->info('No earnings ready to be marked as available.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($earnings as $earning) {
            try {
                $earning->markAvailable();
                $count++;

                $this->info("Marked earning #{$earning->id} as available (Amount: ${$earning->amount})");

            } catch (\Exception $e) {
                Log::error('Failed to mark earning as available', [
                    'earning_id' => $earning->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to process earning #{$earning->id}: {$e->getMessage()}");
            }
        }

        // Update creator profile balances
        $this->info('Updating creator profile balances...');
        $creatorIds = $earnings->pluck('creator_id')->unique();

        foreach ($creatorIds as $creatorId) {
            $profile = \App\Models\Marketplace\MarketplaceCreatorProfile::where('user_id', $creatorId)->first();
            if ($profile) {
                $profile->updateBalances();
                $this->info("Updated balance for creator #{$creatorId}");
            }
        }

        $this->info("Successfully processed {$count} earnings.");

        Log::info('Marketplace earnings processed', [
            'count' => $count,
            'creators_updated' => $creatorIds->count(),
        ]);

        return self::SUCCESS;
    }
}
