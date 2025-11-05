<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatChangeHistory extends Model
{
    protected $table = 'seat_change_history';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'old_seats',
        'new_seats',
        'seats_changed',
        'proration_amount',
        'currency',
        'stripe_invoice_id',
        'status',
        'payment_status',
        'failure_reason',
        'initiated_by',
        'ip_address',
        'notes',
    ];

    protected $casts = [
        'proration_amount' => 'decimal:2',
        'old_seats' => 'integer',
        'new_seats' => 'integer',
        'seats_changed' => 'integer',
    ];

    /**
     * Get the user that owns the seat change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription associated with this seat change.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(\Wave\Subscription::class);
    }

    /**
     * Check if this was a seat increase.
     */
    public function isIncrease(): bool
    {
        return $this->seats_changed > 0;
    }

    /**
     * Check if this was a seat decrease.
     */
    public function isDecrease(): bool
    {
        return $this->seats_changed < 0;
    }

    /**
     * Scope to get suspicious patterns (increase followed by cancellation).
     */
    public function scopeSuspiciousPatterns($query, int $hoursWindow = 24)
    {
        return $query->where('seats_changed', '>', 0)
            ->where('created_at', '>=', now()->subHours($hoursWindow))
            ->whereHas('subscription', function ($q) {
                $q->where('status', 'cancelled')
                    ->orWhere('cancel_at_period_end', true);
            });
    }

    /**
     * Scope to get failed payments.
     */
    public function scopeFailedPayments($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get recent changes for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId)->latest();
    }
}
