<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AccountWarmupService;
use App\Models\AccountWarmup;

class RunAccountWarmup extends Command
{
    protected $signature = 'warmup:run';
    protected $description = 'Process active account warmup activities';

    public function handle()
    {
        $this->info('Starting account warmup processing...');

        $service = app(AccountWarmupService::class);

        try {
            // Find all active warmups
            $activeWarmups = AccountWarmup::active()
                ->where('current_day', '<=', \DB::raw('scheduled_days'))
                ->count();

            $this->info("Found {$activeWarmups} active warmup(s)");

            // Process warmups
            $service->processWarmups();

            $this->info('Account warmup processing completed successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing warmups: ' . $e->getMessage());
            \Log::error('Warmup command failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }
}
