<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'description',
        'model_type',
        'model_id',
        'properties',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that the activity was performed on
     */
    public function model(): MorphTo
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to get recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>', now()->subDays($days));
    }

    /**
     * Scope to get suspicious activities
     */
    public function scopeSuspicious($query)
    {
        return $query->where('type', 'suspicious')
            ->orWhere('type', 'failed_login');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    /**
     * Get user's name or 'System' if no user
     */
    public function getUserNameAttribute(): string
    {
        return $this->user?->name ?? 'System';
    }

    /**
     * Get browser from user agent
     */
    public function getBrowserAttribute(): ?string
    {
        if (!$this->user_agent) {
            return null;
        }

        if (str_contains($this->user_agent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($this->user_agent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($this->user_agent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($this->user_agent, 'Edge')) {
            return 'Edge';
        }

        return 'Unknown';
    }

    /**
     * Get operating system from user agent
     */
    public function getOperatingSystemAttribute(): ?string
    {
        if (!$this->user_agent) {
            return null;
        }

        if (str_contains($this->user_agent, 'Windows')) {
            return 'Windows';
        } elseif (str_contains($this->user_agent, 'Mac')) {
            return 'macOS';
        } elseif (str_contains($this->user_agent, 'Linux')) {
            return 'Linux';
        } elseif (str_contains($this->user_agent, 'Android')) {
            return 'Android';
        } elseif (str_contains($this->user_agent, 'iOS')) {
            return 'iOS';
        }

        return 'Unknown';
    }

    /**
     * Check if activity is suspicious
     */
    public function isSuspicious(): bool
    {
        return in_array($this->type, ['suspicious', 'failed_login']);
    }

    /**
     * Get activity icon
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'login' => 'login',
            'logout' => 'logout',
            'create' => 'plus-circle',
            'update' => 'edit',
            'delete' => 'trash',
            'view' => 'eye',
            'export' => 'download',
            'import' => 'upload',
            'settings_change' => 'settings',
            'permission_change' => 'shield',
            'password_change' => 'key',
            '2fa_enable', '2fa_disable' => 'shield-check',
            'failed_login', 'suspicious' => 'alert-triangle',
            default => 'activity',
        };
    }

    /**
     * Get activity color
     */
    public function getColorAttribute(): string
    {
        return match($this->type) {
            'create' => 'success',
            'update' => 'info',
            'delete' => 'danger',
            'failed_login', 'suspicious' => 'warning',
            'login' => 'primary',
            default => 'secondary',
        };
    }
}
