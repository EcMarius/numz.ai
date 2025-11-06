<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPackagePurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credit_package_id',
        'payment_transaction_id',
        'price_paid',
        'credits_received',
        'bonus_credits',
        'status',
        'metadata',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'credits_received' => 'decimal:2',
        'bonus_credits' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditPackage(): BelongsTo
    {
        return $this->belongsTo(CreditPackage::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    /**
     * Mark purchase as completed
     */
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark purchase as failed
     */
    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    /**
     * Mark purchase as refunded
     */
    public function markRefunded(): void
    {
        $this->update(['status' => 'refunded']);
    }

    /**
     * Check if purchase is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if purchase is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get total credits (base + bonus)
     */
    public function getTotalCreditsAttribute(): float
    {
        return $this->credits_received + $this->bonus_credits;
    }

    /**
     * Scope: Completed purchases
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Pending purchases
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
