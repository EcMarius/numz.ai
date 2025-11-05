<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;

class FacebookWarmupStrategy extends BaseWarmupStrategy
{
    public function performActivity(AccountWarmup $warmup): void
    {
        // Placeholder for Facebook warmup strategy
        // To be implemented when Facebook posting is supported
        \Log::info('Facebook warmup not yet implemented', ['warmup_id' => $warmup->id]);
    }

    public function getActivityLimits(string $phase): array
    {
        return [
            'comments_min' => 1,
            'comments_max' => 2,
            'posts_min' => 0,
            'posts_max' => 1,
        ];
    }
}
