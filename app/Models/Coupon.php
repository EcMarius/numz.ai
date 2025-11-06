<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'description',
        'max_uses',
        'max_uses_per_user',
        'uses_count',
        'product_ids',
        'excluded_product_ids',
        'minimum_order_amount',
        'applies_to_renewals',
        'applies_to_new_orders',
        'allowed_user_ids',
        'allowed_email_domains',
        'first_order_only',
        'can_stack',
        'stack_with_coupon_ids',
        'starts_at',
        'expires_at',
        'is_active',
        'is_recurring',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'excluded_product_ids' => 'array',
        'allowed_user_ids' => 'array',
        'allowed_email_domains' => 'array',
        'stack_with_coupon_ids' => 'array',
        'metadata' => 'array',
        'applies_to_renewals' => 'boolean',
        'applies_to_new_orders' => 'boolean',
        'first_order_only' => 'boolean',
        'can_stack' => 'boolean',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check date range
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        // Check max uses
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this coupon
     */
    public function canBeUsedBy(User $user, bool $isFirstOrder = false): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check first order only
        if ($this->first_order_only && !$isFirstOrder) {
            return false;
        }

        // Check allowed users
        if ($this->allowed_user_ids && !in_array($user->id, $this->allowed_user_ids)) {
            return false;
        }

        // Check email domains
        if ($this->allowed_email_domains) {
            $emailDomain = '@' . explode('@', $user->email)[1];
            if (!in_array($emailDomain, $this->allowed_email_domains)) {
                return false;
            }
        }

        // Check max uses per user
        $userUsages = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsages >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon applies to product
     */
    public function appliesToProduct(int $productId): bool
    {
        // Check excluded products first
        if ($this->excluded_product_ids && in_array($productId, $this->excluded_product_ids)) {
            return false;
        }

        // If no specific products, applies to all
        if (!$this->product_ids) {
            return true;
        }

        return in_array($productId, $this->product_ids);
    }

    /**
     * Calculate discount for a given amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            return round(($amount * $this->value) / 100, 2);
        }

        if ($this->type === 'fixed') {
            return min($this->value, $amount); // Don't exceed total
        }

        if ($this->type === 'credits') {
            return $this->value; // Credits applied separately
        }

        return 0;
    }

    /**
     * Check if can stack with another coupon
     */
    public function canStackWith(Coupon $otherCoupon): bool
    {
        if (!$this->can_stack) {
            return false;
        }

        // If specific stack list exists, check it
        if ($this->stack_with_coupon_ids) {
            return in_array($otherCoupon->id, $this->stack_with_coupon_ids);
        }

        // Otherwise, can stack if other coupon also allows stacking
        return $otherCoupon->can_stack;
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Record usage
     */
    public function recordUsage(User $user, ?int $invoiceId, float $discountAmount, string $orderType = 'new'): CouponUsage
    {
        $this->incrementUsage();

        return $this->usages()->create([
            'user_id' => $user->id,
            'invoice_id' => $invoiceId,
            'discount_amount' => $discountAmount,
            'order_type' => $orderType,
            'metadata' => [
                'applied_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get formatted value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }

        if ($this->type === 'fixed') {
            return '$' . number_format($this->value, 2);
        }

        if ($this->type === 'credits') {
            return '$' . number_format($this->value, 2) . ' credits';
        }

        return '';
    }

    /**
     * Scope: Active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope: Expired coupons
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope: Exhausted coupons (max uses reached)
     */
    public function scopeExhausted($query)
    {
        return $query->whereNotNull('max_uses')
            ->whereColumn('uses_count', '>=', 'max_uses');
    }
}
