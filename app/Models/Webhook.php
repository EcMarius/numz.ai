<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
        'failure_count',
        'max_retries',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if webhook is subscribed to an event
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Increment failure count
     */
    public function incrementFailureCount(): void
    {
        $this->increment('failure_count');

        // Disable webhook if it exceeds max failures
        if ($this->failure_count >= 10) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Reset failure count
     */
    public function resetFailureCount(): void
    {
        $this->update(['failure_count' => 0]);
    }

    /**
     * Generate signature for payload
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
