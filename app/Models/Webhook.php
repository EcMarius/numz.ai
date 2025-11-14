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
        'active',
        'retry_attempts',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(WebhookCall::class);
    }

    /**
     * Check if webhook should handle this event
     */
    public function handlesEvent(string $event): bool
    {
        return $this->active && in_array($event, $this->events);
    }
}
