<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;

abstract class BaseWarmupStrategy implements WarmupStrategyInterface
{
    public function getPhaseForDay(int $day): string
    {
        return match(true) {
            $day >= 1 && $day <= 3 => 'introduction',
            $day >= 4 && $day <= 7 => 'engagement',
            $day >= 8 && $day <= 14 => 'reputation',
            default => 'reputation'
        };
    }

    public function shouldUseAI(AccountWarmup $warmup): bool
    {
        $user = $warmup->user;

        if (!$user || !$user->subscription('default')) {
            return false;
        }

        $plan = $user->subscription('default')->plan;

        // Check if plan has AI post management enabled
        return $plan->getCustomProperty('evenleads.ai_post_management', false);
    }

    /**
     * Generate random time within active hours (8 AM - 10 PM)
     */
    protected function getRandomActivityTime(): \Carbon\Carbon
    {
        $now = now();
        $hour = rand(8, 22); // 8 AM to 10 PM
        $minute = rand(0, 59);

        return $now->setHour($hour)->setMinute($minute)->setSecond(0);
    }

    /**
     * Select random subreddit/group from settings
     */
    protected function selectRandomTarget(AccountWarmup $warmup): ?string
    {
        $settings = $warmup->settings ?? [];
        $targets = $settings['targets'] ?? [];

        if (empty($targets)) {
            return null;
        }

        return $targets[array_rand($targets)];
    }
}
