<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'previous_version',
        'update_type',
        'status',
        'changelog',
        'download_url',
        'checksum',
        'download_size',
        'initiated_by',
        'started_at',
        'completed_at',
        'failed_at',
        'error_message',
        'backup_info',
        'auto_update',
        'progress_percentage',
        'update_steps',
    ];

    protected $casts = [
        'backup_info' => 'array',
        'update_steps' => 'array',
        'auto_update' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the update
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get backups created for this update
     */
    public function backups(): HasMany
    {
        return $this->hasMany(UpdateBackup::class);
    }

    /**
     * Mark update as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'downloading',
            'started_at' => now(),
            'progress_percentage' => 0,
        ]);
    }

    /**
     * Mark update as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    /**
     * Mark update as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $percentage, string $step = null): void
    {
        $updates = [
            'progress_percentage' => min(100, max(0, $percentage)),
        ];

        if ($step) {
            $steps = $this->update_steps ?? [];
            $steps[] = [
                'step' => $step,
                'completed_at' => now()->toISOString(),
            ];
            $updates['update_steps'] = $steps;
        }

        $this->update($updates);
    }

    /**
     * Check if update can be rolled back
     */
    public function canRollback(): bool
    {
        return $this->status === 'completed' &&
               $this->backups()->where('is_restorable', true)->exists();
    }

    /**
     * Get the latest completed update
     */
    public static function getLatest(): ?self
    {
        return self::where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'downloading', 'installing' => 'info',
            'failed' => 'danger',
            'rolled_back' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->download_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->download_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get duration in human readable format
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? $this->failed_at ?? now();
        $diff = $this->started_at->diff($end);

        if ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        } elseif ($diff->i > 0) {
            return $diff->i . 'm ' . $diff->s . 's';
        } else {
            return $diff->s . 's';
        }
    }
}
