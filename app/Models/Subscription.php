<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'subscription_number',
        'status',
        'billing_cycle',
        'quantity',
        'amount',
        'currency',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'next_billing_date',
        'last_billing_date',
        'cancelled_at',
        'cancellation_reason',
        'payment_method',
        'gateway_subscription_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'last_billing_date' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (!$subscription->subscription_number) {
                $subscription->subscription_number = self::generateSubscriptionNumber();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Generate unique subscription number
     */
    public static function generateSubscriptionNumber(): string
    {
        $prefix = 'SUB';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        $subscriptionNumber = "{$prefix}-{$date}-{$random}";

        while (self::where('subscription_number', $subscriptionNumber)->exists()) {
            $random = strtoupper(substr(uniqid(), -6));
            $subscriptionNumber = "{$prefix}-{$date}-{$random}";
        }

        return $subscriptionNumber;
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               (!$this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if subscription is cancelled
     */
    public function cancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if subscription is past due
     */
    public function pastDue(): bool
    {
        return $this->status === 'past_due';
    }

    /**
     * Cancel subscription
     */
    public function cancel(bool $immediately = false, string $reason = null): void
    {
        if ($immediately) {
            $this->update([
                'status' => 'cancelled',
                'ends_at' => now(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
        } else {
            // Cancel at end of billing period
            $this->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'ends_at' => $this->next_billing_date,
            ]);
        }
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume(): void
    {
        if ($this->cancelled() && $this->ends_at && $this->ends_at->isFuture()) {
            $this->update([
                'status' => 'active',
                'ends_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);
        }
    }

    /**
     * Pause subscription
     */
    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
        ]);
    }

    /**
     * Unpause subscription
     */
    public function unpause(): void
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    /**
     * Mark as past due
     */
    public function markAsPastDue(): void
    {
        $this->update([
            'status' => 'past_due',
        ]);
    }

    /**
     * Calculate next billing date
     */
    public function calculateNextBillingDate(): \Carbon\Carbon
    {
        $baseDate = $this->next_billing_date ?? $this->starts_at ?? now();

        return match($this->billing_cycle) {
            'monthly' => $baseDate->copy()->addMonth(),
            'quarterly' => $baseDate->copy()->addMonths(3),
            'semi_annually' => $baseDate->copy()->addMonths(6),
            'annually' => $baseDate->copy()->addYear(),
            'biennially' => $baseDate->copy()->addYears(2),
            'triennially' => $baseDate->copy()->addYears(3),
            default => $baseDate->copy()->addMonth(),
        };
    }

    /**
     * Process billing cycle
     */
    public function processBilling(): Invoice
    {
        // Create invoice
        $invoice = Invoice::create([
            'user_id' => $this->user_id,
            'subscription_id' => $this->id,
            'invoice_type' => 'subscription',
            'status' => 'pending',
            'subtotal' => $this->amount,
            'tax' => $this->amount * 0.1, // Example tax rate
            'total' => $this->amount * 1.1,
            'due_date' => $this->next_billing_date,
            'items' => [[
                'description' => "{$this->product->name} - {$this->billing_cycle} subscription",
                'quantity' => $this->quantity,
                'unit_price' => $this->amount,
                'total' => $this->amount,
            ]],
        ]);

        // Update subscription
        $this->update([
            'last_billing_date' => $this->next_billing_date,
            'next_billing_date' => $this->calculateNextBillingDate(),
        ]);

        return $invoice;
    }

    /**
     * Swap to a different product
     */
    public function swap(Product $newProduct): void
    {
        $this->update([
            'product_id' => $newProduct->id,
            'amount' => $newProduct->price,
        ]);
    }

    /**
     * Increment quantity
     */
    public function incrementQuantity(int $count = 1): void
    {
        $this->update([
            'quantity' => $this->quantity + $count,
            'amount' => $this->amount * ($this->quantity + $count) / $this->quantity,
        ]);
    }

    /**
     * Decrement quantity
     */
    public function decrementQuantity(int $count = 1): void
    {
        $newQuantity = max(1, $this->quantity - $count);

        $this->update([
            'quantity' => $newQuantity,
            'amount' => $this->amount * $newQuantity / $this->quantity,
        ]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'trialing' => 'info',
            'past_due' => 'warning',
            'paused' => 'warning',
            'cancelled' => 'danger',
            'expired' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get subscriptions due for billing
     */
    public static function getDueForBilling(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->where('next_billing_date', '<=', now())
            ->get();
    }

    /**
     * Get expiring trials
     */
    public static function getExpiringTrials(int $days = 3): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->get();
    }
}
