<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create an audit log entry
     *
     * @param string $event
     * @param mixed $auditable
     * @param array $oldValues
     * @param array $newValues
     * @param array $tags
     * @return static
     */
    public static function log(
        string $event,
        $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $tags = []
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => $tags,
        ]);
    }

    /**
     * Get formatted description of the audit event
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user ? $this->user->name : 'System';
        $action = str_replace('_', ' ', $this->event);

        if ($this->auditable_type) {
            $model = class_basename($this->auditable_type);
            return "{$user} {$action} {$model} #{$this->auditable_id}";
        }

        return "{$user} {$action}";
    }
}
