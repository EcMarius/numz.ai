<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Reseller extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_reseller_id',
        'reseller_tier_id',
        'company_name',
        'status',
        'reseller_code',
        'primary_contact_name',
        'primary_contact_email',
        'primary_contact_phone',
        'business_type',
        'tax_id',
        'business_address',
        'custom_domain',
        'company_logo',
        'company_favicon',
        'brand_colors',
        'support_email',
        'support_phone',
        'terms_of_service',
        'privacy_policy',
        'custom_pricing_enabled',
        'global_discount_percentage',
        'commission_rate',
        'recurring_commission',
        'payout_method',
        'payout_details',
        'minimum_payout',
        'total_customers',
        'total_services',
        'total_domains',
        'total_revenue',
        'total_commission_earned',
        'total_commission_paid',
        'pending_commission',
        'activated_at',
        'suspended_at',
        'cancelled_at',
        'api_key',
        'api_key_last_used_at',
        'notes',
    ];

    protected $casts = [
        'brand_colors' => 'array',
        'payout_details' => 'array',
        'custom_pricing_enabled' => 'boolean',
        'recurring_commission' => 'boolean',
        'activated_at' => 'date',
        'suspended_at' => 'date',
        'cancelled_at' => 'date',
        'api_key_last_used_at' => 'datetime',
    ];

    /**
     * Get the user account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reseller tier
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(ResellerTier::class, 'reseller_tier_id');
    }

    /**
     * Get parent reseller (for multi-level)
     */
    public function parentReseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'parent_reseller_id');
    }

    /**
     * Get child resellers
     */
    public function childResellers(): HasMany
    {
        return $this->hasMany(Reseller::class, 'parent_reseller_id');
    }

    /**
     * Get reseller customers
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'reseller_customers')
            ->withTimestamps()
            ->withPivot('assigned_at');
    }

    /**
     * Get custom pricing
     */
    public function pricing(): HasMany
    {
        return $this->hasMany(ResellerPricing::class);
    }

    /**
     * Get commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(ResellerCommission::class);
    }

    /**
     * Get payouts
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(ResellerPayout::class);
    }

    /**
     * Get reports
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ResellerReport::class);
    }

    /**
     * Get support tickets
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(ResellerSupportTicket::class);
    }

    /**
     * Generate reseller code
     */
    public static function generateResellerCode(): string
    {
        do {
            $code = 'RES-' . strtoupper(Str::random(8));
        } while (self::where('reseller_code', $code)->exists());

        return $code;
    }

    /**
     * Generate API key
     */
    public function generateApiKey(): string
    {
        $apiKey = 'rsk_' . Str::random(40);
        $this->update(['api_key' => $apiKey]);
        return $apiKey;
    }

    /**
     * Regenerate API key
     */
    public function regenerateApiKey(): string
    {
        return $this->generateApiKey();
    }

    /**
     * Activate reseller
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'suspended_at' => null,
        ]);
    }

    /**
     * Suspend reseller
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'notes' => $this->notes . "\n\nSuspended: " . $reason,
        ]);
    }

    /**
     * Cancel reseller account
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Add customer to reseller
     */
    public function addCustomer(User $user): void
    {
        if (!$this->customers()->where('user_id', $user->id)->exists()) {
            $this->customers()->attach($user->id, ['assigned_at' => now()]);
            $this->increment('total_customers');
        }
    }

    /**
     * Remove customer from reseller
     */
    public function removeCustomer(User $user): void
    {
        if ($this->customers()->where('user_id', $user->id)->exists()) {
            $this->customers()->detach($user->id);
            $this->decrement('total_customers');
        }
    }

    /**
     * Get effective commission rate
     */
    public function getEffectiveCommissionRate(): float
    {
        return $this->commission_rate ?? $this->tier->commission_rate ?? 0;
    }

    /**
     * Calculate commission for an order
     */
    public function calculateCommission(float $amount): float
    {
        $rate = $this->getEffectiveCommissionRate();
        return round(($amount * $rate) / 100, 2);
    }

    /**
     * Add commission
     */
    public function addCommission(
        Invoice $invoice,
        User $customer,
        string $type,
        float $orderAmount
    ): ResellerCommission {
        $commissionRate = $this->getEffectiveCommissionRate();
        $commissionAmount = $this->calculateCommission($orderAmount);

        $commission = $this->commissions()->create([
            'invoice_id' => $invoice->id,
            'user_id' => $customer->id,
            'type' => $type,
            'status' => 'pending',
            'order_amount' => $orderAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'currency' => $invoice->currency ?? 'USD',
            'earned_date' => now(),
        ]);

        $this->increment('total_commission_earned', $commissionAmount);
        $this->increment('pending_commission', $commissionAmount);

        return $commission;
    }

    /**
     * Get pricing for a product
     */
    public function getPricingForProduct(int $productId): ?ResellerPricing
    {
        return $this->pricing()->where('product_id', $productId)->first();
    }

    /**
     * Check if reseller can add more customers
     */
    public function canAddCustomer(): bool
    {
        return !$this->tier->isCustomerLimitReached($this);
    }

    /**
     * Check if reseller can add more services
     */
    public function canAddService(): bool
    {
        return !$this->tier->isServiceLimitReached($this);
    }

    /**
     * Check if reseller has white-label enabled
     */
    public function hasWhiteLabel(): bool
    {
        return $this->tier->white_label_enabled && !empty($this->custom_domain);
    }

    /**
     * Get brand color
     */
    public function getBrandColor(string $type = 'primary'): ?string
    {
        return $this->brand_colors[$type] ?? null;
    }

    /**
     * Get pending commission total
     */
    public function getPendingCommissionTotal(): float
    {
        return $this->commissions()
            ->where('status', 'pending')
            ->sum('commission_amount');
    }

    /**
     * Get available for payout commission
     */
    public function getAvailableForPayoutCommission(): float
    {
        return $this->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->sum('commission_amount');
    }

    /**
     * Request payout
     */
    public function requestPayout(float $amount = null): ?ResellerPayout
    {
        $available = $this->getAvailableForPayoutCommission();

        if ($available < $this->minimum_payout) {
            return null;
        }

        $amount = $amount ?? $available;

        if ($amount > $available) {
            $amount = $available;
        }

        return $this->payouts()->create([
            'payout_number' => ResellerPayout::generatePayoutNumber(),
            'amount' => $amount,
            'currency' => 'USD',
            'method' => $this->payout_method ?? 'credit',
            'status' => 'pending',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'requested_at' => now(),
        ]);
    }

    /**
     * Get active resellers
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')->get();
    }

    /**
     * Get reseller by code
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('reseller_code', $code)->first();
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'suspended' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
