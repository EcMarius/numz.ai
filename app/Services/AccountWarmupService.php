<?php

namespace App\Services;

use App\Models\AccountWarmup;
use App\Services\WarmupStrategies\RedditWarmupStrategy;
use App\Services\WarmupStrategies\FacebookWarmupStrategy;
use App\Services\WarmupStrategies\TwitterWarmupStrategy;
use App\Services\WarmupStrategies\LinkedInWarmupStrategy;
use Wave\Plugins\SocialAuth\Models\SocialAccount;
use Illuminate\Support\Facades\Log;

class AccountWarmupService
{
    protected $strategies = [];

    public function __construct()
    {
        $this->strategies = [
            'reddit' => new RedditWarmupStrategy(),
            'facebook' => new FacebookWarmupStrategy(),
            'twitter' => new TwitterWarmupStrategy(),
            'x' => new TwitterWarmupStrategy(),
            'linkedin' => new LinkedInWarmupStrategy(),
        ];
    }

    /**
     * Create a new warmup for a social account
     */
    public function createWarmup(int $userId, int $socialAccountId, array $settings = []): AccountWarmup
    {
        $account = SocialAccount::findOrFail($socialAccountId);

        // Verify account belongs to user
        if ($account->user_id !== $userId) {
            throw new \Exception('Unauthorized account access');
        }

        // Check if warmup already exists for this account
        $existing = AccountWarmup::where('social_account_id', $socialAccountId)
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->first();

        if ($existing) {
            throw new \Exception('Warmup already exists for this account');
        }

        $defaultSettings = [
            'targets' => $settings['targets'] ?? ['AskReddit', 'CasualConversation'], // Default subreddits
            'comment_templates' => $settings['comment_templates'] ?? [],
            'post_templates' => $settings['post_templates'] ?? [],
            'industry' => $settings['industry'] ?? 'general',
        ];

        return AccountWarmup::create([
            'user_id' => $userId,
            'social_account_id' => $socialAccountId,
            'platform' => $account->provider,
            'status' => 'pending',
            'current_phase' => 'introduction',
            'start_date' => now(),
            'scheduled_days' => $settings['scheduled_days'] ?? 14,
            'current_day' => 0,
            'posts_per_day_min' => $settings['posts_per_day_min'] ?? 1,
            'posts_per_day_max' => $settings['posts_per_day_max'] ?? 3,
            'comments_per_day_min' => $settings['comments_per_day_min'] ?? 2,
            'comments_per_day_max' => $settings['comments_per_day_max'] ?? 5,
            'settings' => $defaultSettings,
            'stats' => [
                'total_comments' => 0,
                'total_posts' => 0,
                'activities' => [],
            ],
        ]);
    }

    /**
     * Start a warmup (change status from pending to active)
     */
    public function startWarmup(int $warmupId): void
    {
        $warmup = AccountWarmup::findOrFail($warmupId);
        $warmup->update([
            'status' => 'active',
            'start_date' => now(),
            'current_day' => 1,
        ]);
    }

    /**
     * Process all active warmups that need activity
     */
    public function processWarmups(): void
    {
        $warmups = AccountWarmup::active()
            ->where('current_day', '<=', \DB::raw('scheduled_days'))
            ->get();

        foreach ($warmups as $warmup) {
            if ($warmup->shouldPerformActivity()) {
                $this->performActivity($warmup);
            }
        }
    }

    /**
     * Perform activity for a specific warmup
     */
    public function performActivity(AccountWarmup $warmup): void
    {
        $strategy = $this->strategies[$warmup->platform] ?? null;

        if (!$strategy) {
            Log::warning('No warmup strategy found for platform', [
                'platform' => $warmup->platform,
                'warmup_id' => $warmup->id,
            ]);
            return;
        }

        try {
            $strategy->performActivity($warmup);
        } catch (\Exception $e) {
            Log::error('Warmup activity failed', [
                'warmup_id' => $warmup->id,
                'platform' => $warmup->platform,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get warmup progress details
     */
    public function getProgress(int $warmupId): array
    {
        $warmup = AccountWarmup::findOrFail($warmupId);

        return [
            'warmup_id' => $warmup->id,
            'current_day' => $warmup->current_day,
            'total_days' => $warmup->scheduled_days,
            'percentage' => $warmup->getProgressPercentage(),
            'phase' => $warmup->current_phase,
            'status' => $warmup->status,
        ];
    }

    /**
     * Get warmup statistics
     */
    public function getStats(int $warmupId): array
    {
        $warmup = AccountWarmup::findOrFail($warmupId);
        $stats = $warmup->stats ?? [];

        return [
            'total_comments' => $stats['total_comments'] ?? 0,
            'total_posts' => $stats['total_posts'] ?? 0,
            'activities_count' => count($stats['activities'] ?? []),
            'last_activity' => $warmup->last_activity_at?->diffForHumans(),
        ];
    }

    /**
     * Pause a warmup
     */
    public function pauseWarmup(int $warmupId): void
    {
        $warmup = AccountWarmup::findOrFail($warmupId);
        $warmup->pause();
    }

    /**
     * Resume a paused warmup
     */
    public function resumeWarmup(int $warmupId): void
    {
        $warmup = AccountWarmup::findOrFail($warmupId);
        $warmup->resume();
    }

    /**
     * Delete (stop and remove) a warmup
     */
    public function deleteWarmup(int $warmupId): void
    {
        $warmup = AccountWarmup::findOrFail($warmupId);
        $warmup->delete();
    }
}
