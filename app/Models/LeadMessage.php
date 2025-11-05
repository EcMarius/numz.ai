<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wave\Plugins\EvenLeads\Models\Lead;

class LeadMessage extends Model
{
    protected $fillable = [
        'user_id',
        'lead_id',
        'message_text',
        'sent_at',
        'is_ai_generated',
        'ai_model_used',
        'direction',
        'platform_message_id',
        'status',
        'channel',
        'scheduled_send_at',
        'is_follow_up',
        'parent_message_id',
        'response_received',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_send_at' => 'datetime',
        'is_ai_generated' => 'boolean',
        'is_follow_up' => 'boolean',
        'response_received' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(LeadMessage::class, 'parent_message_id');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update(['status' => 'delivered']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
