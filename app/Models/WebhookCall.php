<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookCall extends Model
{
    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status_code',
        'response',
        'attempt',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status_code >= 200 && $this->status_code < 300;
    }

    public function shouldRetry(): bool
    {
        return !$this->isSuccessful() &&
               $this->attempt < $this->webhook->retry_attempts;
    }
}
