<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'department',
        'priority',
        'status',
        'assigned_to',
        'related_service_id',
        'related_domain_id',
        'related_invoice_id',
        'last_reply_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'ticket_id');
    }

    public function relatedService(): BelongsTo
    {
        return $this->belongsTo(HostingService::class, 'related_service_id');
    }

    public function relatedDomain(): BelongsTo
    {
        return $this->belongsTo(DomainRegistration::class, 'related_domain_id');
    }

    public function relatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber(): string
    {
        $prefix = config('numz.ticket_prefix', 'TKT');
        $year = now()->year;
        $month = now()->format('m');

        // Get the last ticket number for this month
        $lastTicket = self::where('ticket_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('ticket_number', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s%04d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Check if ticket is open
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress', 'waiting_customer', 'waiting_staff']);
    }

    /**
     * Check if ticket is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Close the ticket
     */
    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Reopen the ticket
     */
    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'closed_at' => null,
        ]);
    }

    /**
     * Assign ticket to a staff member
     */
    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }

    /**
     * Get the first message (original ticket message)
     */
    public function getFirstMessageAttribute()
    {
        return $this->replies()->oldest()->first();
    }

    /**
     * Get the latest reply
     */
    public function getLatestReplyAttribute()
    {
        return $this->replies()->latest()->first();
    }

    /**
     * Get reply count
     */
    public function getReplyCountAttribute(): int
    {
        return $this->replies()->where('is_internal_note', false)->count();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'success',
            'in_progress' => 'info',
            'waiting_customer' => 'warning',
            'waiting_staff' => 'danger',
            'closed' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get priority badge color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'secondary',
            'normal' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }
}
