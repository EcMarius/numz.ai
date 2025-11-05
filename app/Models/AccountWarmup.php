<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wave\Plugins\SocialAuth\Models\SocialAccount;

class AccountWarmup extends Model
{
    protected $fillable = [
        'user_id',
        'social_account_id',
        'platform',
        'status',
        'current_phase',
        'start_date',
        'end_date',
        'scheduled_days',
        'current_day',
        'posts_per_day_min',
        'posts_per_day_max',
        'comments_per_day_min',
        'comments_per_day_max',
        'last_activity_at',
        'settings',
        'stats',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_activity_at' => 'datetime',
        'settings' => 'array',
        'stats' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getCurrentPhaseConfig(): array
    {
        $phases = [
            'introduction' => [
                'days' => [1, 2, 3],
                'comments_min' => 1,
                'comments_max' => 2,
                'posts_min' => 0,
                'posts_max' => 0,
            ],
            'engagement' => [
                'days' => [4, 5, 6, 7],
                'comments_min' => 2,
                'comments_max' => 3,
                'posts_min' => 0,
                'posts_max' => 1, // 1 every 2 days
            ],
            'reputation' => [
                'days' => [8, 9, 10, 11, 12, 13, 14],
                'comments_min' => 3,
                'comments_max' => 4,
                'posts_min' => 1,
                'posts_max' => 1,
            ],
        ];

        return $phases[$this->current_phase] ?? $phases['introduction'];
    }

    public function shouldPerformActivity(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if we've already performed activity today
        if ($this->last_activity_at && $this->last_activity_at->isToday()) {
            return false;
        }

        return true;
    }

    public function recordActivity(string $type, array $data): void
    {
        $stats = $this->stats ?? [];

        if (!isset($stats['activities'])) {
            $stats['activities'] = [];
        }

        $stats['activities'][] = [
            'type' => $type, // 'comment' or 'post'
            'data' => $data,
            'performed_at' => now()->toDateTimeString(),
        ];

        // Update counters
        if (!isset($stats['total_comments'])) $stats['total_comments'] = 0;
        if (!isset($stats['total_posts'])) $stats['total_posts'] = 0;

        if ($type === 'comment') {
            $stats['total_comments']++;
        } elseif ($type === 'post') {
            $stats['total_posts']++;
        }

        $this->update([
            'stats' => $stats,
            'last_activity_at' => now(),
        ]);
    }

    public function advanceDay(): void
    {
        $newDay = $this->current_day + 1;

        // Determine new phase based on day
        $newPhase = match(true) {
            $newDay >= 1 && $newDay <= 3 => 'introduction',
            $newDay >= 4 && $newDay <= 7 => 'engagement',
            $newDay >= 8 && $newDay <= 14 => 'reputation',
            default => 'reputation'
        };

        // Check if warmup is complete
        if ($newDay > $this->scheduled_days) {
            $this->complete();
            return;
        }

        $this->update([
            'current_day' => $newDay,
            'current_phase' => $newPhase,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function fail(string $reason): void
    {
        $stats = $this->stats ?? [];
        $stats['failure_reason'] = $reason;

        $this->update([
            'status' => 'failed',
            'stats' => $stats,
            'end_date' => now(),
        ]);
    }

    public function getProgressPercentage(): int
    {
        if ($this->scheduled_days == 0) return 0;
        return min(100, (int)(($this->current_day / $this->scheduled_days) * 100));
    }
}
