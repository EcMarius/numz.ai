<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status',
        'http_status_code',
        'response_body',
        'error_message',
        'attempts',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if delivery was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if delivery failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if delivery should be retried
     */
    public function shouldRetry(): bool
    {
        $maxRetries = $this->webhook->max_retries ?? 3;
        return $this->status === 'pending' &&
               $this->attempts < $maxRetries &&
               $this->next_retry_at &&
               $this->next_retry_at->isPast();
    }

    /**
     * Mark delivery as delivered
     */
    public function markAsDelivered(int $httpStatusCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'delivered',
            'http_status_code' => $httpStatusCode,
            'response_body' => $responseBody,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark delivery as failed
     */
    public function markAsFailed(string $errorMessage, ?int $httpStatusCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'http_status_code' => $httpStatusCode,
        ]);
    }

    /**
     * Schedule retry
     */
    public function scheduleRetry(): void
    {
        $this->increment('attempts');

        // Exponential backoff: 5min, 15min, 1hour, 3hours
        $delays = [5, 15, 60, 180];
        $delayMinutes = $delays[$this->attempts - 1] ?? 180;

        $this->update([
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);
    }
}
