<?php

namespace App\Services\WarmupStrategies;

use App\Models\AccountWarmup;

interface WarmupStrategyInterface
{
    /**
     * Perform warmup activity for the given warmup record
     */
    public function performActivity(AccountWarmup $warmup): void;

    /**
     * Get the phase name for a given day
     */
    public function getPhaseForDay(int $day): string;

    /**
     * Get activity limits for a specific phase
     */
    public function getActivityLimits(string $phase): array;

    /**
     * Check if this strategy should use AI for content generation
     */
    public function shouldUseAI(AccountWarmup $warmup): bool;
}
