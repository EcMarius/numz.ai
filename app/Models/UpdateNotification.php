<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UpdateNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'notification_type',
        'message',
        'metadata',
        'is_read',
        'is_dismissed',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as dismissed
     */
    public function dismiss(): void
    {
        $this->update(['is_dismissed' => true]);
    }

    /**
     * Get unread notifications for user
     */
    public static function getUnreadForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->where('is_read', false)
            ->where('is_dismissed', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get notification icon based on type
     */
    public function getIconAttribute(): string
    {
        return match($this->notification_type) {
            'new_version' => 'heroicon-o-arrow-up-circle',
            'update_started' => 'heroicon-o-arrow-path',
            'update_completed' => 'heroicon-o-check-circle',
            'update_failed' => 'heroicon-o-x-circle',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * Get notification color based on type
     */
    public function getColorAttribute(): string
    {
        return match($this->notification_type) {
            'new_version' => 'info',
            'update_started' => 'warning',
            'update_completed' => 'success',
            'update_failed' => 'danger',
            default => 'gray',
        };
    }
}
