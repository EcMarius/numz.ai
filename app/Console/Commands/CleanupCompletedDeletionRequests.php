<?php

namespace App\Console\Commands;

use App\Models\DataDeletionRequest;
use Illuminate\Console\Command;

class CleanupCompletedDeletionRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deletion-requests:cleanup
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all completed data deletion requests from the database (GDPR compliance)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🗑️  Data Deletion Requests Cleanup');
        $this->newLine();

        // Count requests
        $completedCount = DataDeletionRequest::where('status', 'completed')->count();
        $allCount = DataDeletionRequest::count();

        $this->info("Found {$completedCount} completed request(s) out of {$allCount} total.");
        $this->newLine();

        if ($completedCount === 0) {
            $this->info('✅ No completed requests to clean up.');
            return Command::SUCCESS;
        }

        // Show sample data
        $this->table(
            ['ID', 'Email', 'Status', 'Completed At'],
            DataDeletionRequest::where('status', 'completed')
                ->limit(5)
                ->get()
                ->map(fn($r) => [
                    $r->id,
                    $r->email,
                    $r->status,
                    $r->completed_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ])
                ->toArray()
        );

        if ($completedCount > 5) {
            $this->info("... and " . ($completedCount - 5) . " more");
        }

        $this->newLine();

        // Confirm deletion
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to permanently delete these completed requests?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Delete completed requests
        $deleted = DataDeletionRequest::where('status', 'completed')->delete();

        $this->newLine();
        $this->info("✅ Deleted {$deleted} completed data deletion request(s).");
        $this->info('   (Keeping the request = keeping user PII - not GDPR compliant)');

        return Command::SUCCESS;
    }
}
