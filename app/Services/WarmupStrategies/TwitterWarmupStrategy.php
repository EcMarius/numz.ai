<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;

class TwitterWarmupStrategy extends BaseWarmupStrategy
{
    public function performActivity(AccountWarmup $warmup): void
    {
        // Placeholder for Twitter warmup strategy
        // To be implemented when Twitter posting is supported
        \Log::info('Twitter warmup not yet implemented', ['warmup_id' => $warmup->id]);
    }

    public function getActivityLimits(string $phase): array
    {
        return [
            'comments_min' => 2,
            'comments_max' => 3,
            'posts_min' => 1,
            'posts_max' => 2,
        ];
    }
}
