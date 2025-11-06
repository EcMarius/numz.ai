<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicketReply extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_staff_reply',
        'is_internal_note',
        'attachments',
    ];

    protected $casts = [
        'is_staff_reply' => 'boolean',
        'is_internal_note' => 'boolean',
        'attachments' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentFiles(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'reply_id');
    }

    /**
     * Check if reply is from staff
     */
    public function isFromStaff(): bool
    {
        return $this->is_staff_reply;
    }

    /**
     * Check if reply is internal note (staff only)
     */
    public function isInternalNote(): bool
    {
        return $this->is_internal_note;
    }

    /**
     * Update ticket's last_reply_at timestamp when reply is created
     */
    protected static function booted(): void
    {
        static::created(function (SupportTicketReply $reply) {
            // Update ticket's last_reply_at timestamp
            $reply->ticket()->update(['last_reply_at' => now()]);

            // Update ticket status based on who replied
            if ($reply->is_staff_reply && !$reply->is_internal_note) {
                // Staff replied, set status to waiting_customer
                $reply->ticket()->update(['status' => 'waiting_customer']);
            } elseif (!$reply->is_staff_reply) {
                // Customer replied, set status to waiting_staff
                $reply->ticket()->update(['status' => 'waiting_staff']);
            }
        });
    }
}
