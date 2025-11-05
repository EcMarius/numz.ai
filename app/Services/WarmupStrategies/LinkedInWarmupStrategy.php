<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;

class LinkedInWarmupStrategy extends BaseWarmupStrategy
{
    public function performActivity(AccountWarmup $warmup): void
    {
        // Placeholder for LinkedIn warmup strategy
        // To be implemented when LinkedIn posting is supported
        \Log::info('LinkedIn warmup not yet implemented', ['warmup_id' => $warmup->id]);
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
