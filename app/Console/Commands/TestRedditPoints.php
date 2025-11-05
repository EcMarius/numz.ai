<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Wave\Plugins\EvenLeads\Models\PlatformConnection;

class TestRedditPoints extends Command
{
    protected $signature = 'test:reddit-points {subreddit=webdev}';
    protected $description = 'Test Reddit API points extraction - fetch one post and show raw response';

    public function handle()
    {
        $this->info('🧪 Testing Reddit API Points Extraction...');
        $this->newLine();

        $subreddit = $this->argument('subreddit');

        // Get Reddit connection
        $connection = PlatformConnection::where('platform', 'reddit')
            ->first();

        if (!$connection) {
            $this->error('❌ No active Reddit connection found!');
            return 1;
        }

        $this->info("✅ Found Reddit connection: {$connection->account_name}");
        $this->newLine();

        // Fetch ONE post from Reddit API
        $this->info("🔍 Fetching latest post from r/{$subreddit}...");

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $connection->access_token,
                'User-Agent' => config('evenleads.reddit.user_agent'),
            ])->get("https://oauth.reddit.com/r/{$subreddit}/new", [
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                $this->error("❌ Reddit API request failed: HTTP {$response->status()}");
                $this->error("Response: {$response->body()}");
                return 1;
            }

            $this->info("✅ Reddit API request successful!");
            $this->newLine();

            // Get raw JSON
            $rawJson = $response->body();
            $this->line("📥 RAW REDDIT API RESPONSE:");
            $this->line(str_repeat('=', 80));
            $this->line($rawJson);
            $this->line(str_repeat('=', 80));
            $this->newLine();

            // Parse response
            $data = $response->json();
            $posts = $data['data']['children'] ?? [];

            if (empty($posts)) {
                $this->warn('⚠️  No posts found in response');
                return 1;
            }

            $firstPost = $posts[0]['data'];

            $this->info("📊 PARSED POST DATA:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['id', $firstPost['id'] ?? 'N/A'],
                    ['title', substr($firstPost['title'] ?? 'N/A', 0, 60) . '...'],
                    ['author', $firstPost['author'] ?? 'N/A'],
                    ['subreddit', $firstPost['subreddit'] ?? 'N/A'],
                    ['ups (upvotes)', $firstPost['ups'] ?? 'N/A'],
                    ['score', $firstPost['score'] ?? 'N/A'],
                    ['num_comments', $firstPost['num_comments'] ?? 'N/A'],
                    ['created_utc', $firstPost['created_utc'] ?? 'N/A'],
                    ['permalink', $firstPost['permalink'] ?? 'N/A'],
                ]
            );

            $this->newLine();

            // Test points extraction logic
            $extractedPoints = $firstPost['ups'] ?? $firstPost['score'] ?? 0;

            $this->info("🎯 POINTS EXTRACTION TEST:");
            $this->line("  Formula: \$postData['ups'] ?? \$postData['score'] ?? 0");
            $this->line("  Result: {$extractedPoints} points");
            $this->newLine();

            if ($extractedPoints > 0) {
                $this->info("✅ Points extraction SUCCESSFUL! Got {$extractedPoints} points");
            } else {
                $this->warn("⚠️  Points extraction resulted in 0 - check Reddit API response");
            }

            $this->newLine();
            $this->info("🔑 ALL AVAILABLE KEYS IN RESPONSE:");
            $this->line(implode(', ', array_keys($firstPost)));

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->error("Trace: {$e->getTraceAsString()}");
            return 1;
        }
    }
}
