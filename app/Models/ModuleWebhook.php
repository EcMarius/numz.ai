<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModuleWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_configuration_id',
        'event_type',
        'webhook_url',
        'secret',
        'payload',
        'response',
        'status',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function moduleConfiguration(): BelongsTo
    {
        return $this->belongsTo(ModuleConfiguration::class);
    }
}
