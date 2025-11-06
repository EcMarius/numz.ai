<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target',
        'target_user_ids',
        'start_date',
        'end_date',
        'is_published',
        'show_on_dashboard',
        'require_acknowledgment',
        'created_by',
    ];

    protected $casts = [
        'target_user_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
        'show_on_dashboard' => 'boolean',
        'require_acknowledgment' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(AnnouncementAcknowledgment::class);
    }

    /**
     * Check if announcement is active
     */
    public function isActive(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        $now = now()->toDateString();

        if ($this->start_date > $now) {
            return false;
        }

        if ($this->end_date && $this->end_date < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if user should see this announcement
     */
    public function isVisibleToUser(User $user): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->target === 'all') {
            return true;
        }

        if ($this->target === 'customers' && !$user->isAdmin()) {
            return true;
        }

        if ($this->target === 'admins' && $user->isAdmin()) {
            return true;
        }

        if ($this->target === 'specific' && $this->target_user_ids) {
            return in_array($user->id, $this->target_user_ids);
        }

        return false;
    }

    /**
     * Check if user has acknowledged
     */
    public function hasUserAcknowledged(User $user): bool
    {
        return $this->acknowledgments()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Record acknowledgment
     */
    public function acknowledge(User $user): void
    {
        $this->acknowledgments()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Scope for active announcements
     */
    public function scopeActive($query)
    {
        return $query->where('is_published', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for dashboard announcements
     */
    public function scopeDashboard($query)
    {
        return $query->where('show_on_dashboard', true);
    }

    /**
     * Get acknowledgment percentage
     */
    public function getAcknowledgmentPercentageAttribute(): float
    {
        // This would need to calculate based on target users
        $targetCount = $this->getTargetUserCount();
        if ($targetCount === 0) {
            return 0;
        }

        $acknowledgedCount = $this->acknowledgments()->count();
        return round(($acknowledgedCount / $targetCount) * 100, 2);
    }

    /**
     * Get target user count
     */
    private function getTargetUserCount(): int
    {
        if ($this->target === 'all') {
            return User::count();
        }

        if ($this->target === 'specific' && $this->target_user_ids) {
            return count($this->target_user_ids);
        }

        // Would need more complex logic for customers/admins
        return 0;
    }
}
