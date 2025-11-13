<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The table is immutable (no updates or deletes)
     */
    public static function boot()
    {
        parent::boot();

        // Prevent updates
        static::updating(function () {
            return false;
        });

        // Prevent deletes (except through special method)
        static::deleting(function ($model) {
            if (!$model->force_delete) {
                return false;
            }
        });
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter by model
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('auditable_type', $modelType);

        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope to get recent audits
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>', now()->subDays($days));
    }

    /**
     * Get modified fields
     */
    public function getModifiedFields(): array
    {
        $modified = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $modified[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $modified;
    }

    /**
     * Get event description
     */
    public function getDescriptionAttribute(): string
    {
        $userName = $this->user?->name ?? 'System';
        $modelName = $this->auditable_type ? class_basename($this->auditable_type) : 'record';

        return match($this->event) {
            'created' => "{$userName} created {$modelName} #{$this->auditable_id}",
            'updated' => "{$userName} updated {$modelName} #{$this->auditable_id}",
            'deleted' => "{$userName} deleted {$modelName} #{$this->auditable_id}",
            'restored' => "{$userName} restored {$modelName} #{$this->auditable_id}",
            default => "{$userName} performed {$this->event} on {$modelName} #{$this->auditable_id}",
        };
    }

    /**
     * Check if audit has changes
     */
    public function hasChanges(): bool
    {
        return !empty($this->getModifiedFields());
    }

    /**
     * Get changes summary
     */
    public function getChangesSummary(): string
    {
        $modified = $this->getModifiedFields();

        if (empty($modified)) {
            return 'No changes';
        }

        $changes = [];
        foreach ($modified as $field => $values) {
            $changes[] = ucfirst($field);
        }

        return implode(', ', $changes) . ' changed';
    }

    /**
     * Force delete audit log (for cleanup)
     */
    public function forceDeleteAudit(): bool
    {
        $this->force_delete = true;
        return $this->delete();
    }

    /**
     * Get audit color based on event
     */
    public function getColorAttribute(): string
    {
        return match($this->event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'restored' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get audit icon based on event
     */
    public function getIconAttribute(): string
    {
        return match($this->event) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'restored' => 'refresh-cw',
            default => 'activity',
        };
    }

    /**
     * Export audit to array
     */
    public function toExport(): array
    {
        return [
            'date' => $this->created_at->toDateTimeString(),
            'user' => $this->user?->name ?? 'System',
            'event' => $this->event,
            'model' => $this->auditable_type ? class_basename($this->auditable_type) : null,
            'model_id' => $this->auditable_id,
            'changes' => $this->getChangesSummary(),
            'ip_address' => $this->ip_address,
        ];
    }
}
