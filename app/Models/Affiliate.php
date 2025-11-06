<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'affiliate_tier_id',
        'referred_by_affiliate_id',
        'affiliate_code',
        'status',
        'payment_method',
        'payment_details',
        'company_name',
        'website',
        'promotional_methods',
        'total_clicks',
        'total_signups',
        'total_conversions',
        'total_sales',
        'total_commission_earned',
        'total_commission_paid',
        'pending_commission',
        'conversion_rate',
        'approved_at',
        'suspended_at',
        'suspension_reason',
        'notes',
    ];

    protected $casts = [
        'payment_details' => 'array',
        'approved_at' => 'date',
        'suspended_at' => 'date',
    ];

    /**
     * Get the user account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the affiliate tier
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(AffiliateTier::class, 'affiliate_tier_id');
    }

    /**
     * Get referring affiliate (for multi-tier)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'referred_by_affiliate_id');
    }

    /**
     * Get referred affiliates
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Affiliate::class, 'referred_by_affiliate_id');
    }

    /**
     * Get clicks
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    /**
     * Get referral signups
     */
    public function signups(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class);
    }

    /**
     * Get commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * Get payouts
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    /**
     * Get campaigns
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(AffiliateCampaign::class);
    }

    /**
     * Get leaderboard entries
     */
    public function leaderboardEntries(): HasMany
    {
        return $this->hasMany(AffiliateLeaderboard::class);
    }

    /**
     * Get fraud alerts
     */
    public function fraudAlerts(): HasMany
    {
        return $this->hasMany(AffiliateFraudAlert::class);
    }

    /**
     * Generate affiliate code
     */
    public static function generateAffiliateCode(): string
    {
        do {
            $code = 'AFF-' . strtoupper(Str::random(8));
        } while (self::where('affiliate_code', $code)->exists());

        return $code;
    }

    /**
     * Track click
     */
    public function trackClick(array $data = []): AffiliateClick
    {
        $click = $this->clicks()->create([
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'referrer_url' => $data['referrer_url'] ?? request()->header('referer'),
            'landing_page' => $data['landing_page'] ?? request()->url(),
            'country' => $data['country'] ?? null,
            'device_type' => $data['device_type'] ?? $this->detectDeviceType(),
            'browser' => $data['browser'] ?? $this->detectBrowser(),
            'clicked_at' => now(),
        ]);

        $this->increment('total_clicks');

        return $click;
    }

    /**
     * Add referral
     */
    public function addReferral(User $user, ?AffiliateClick $click = null): AffiliateReferral
    {
        $referral = $this->signups()->create([
            'user_id' => $user->id,
            'click_id' => $click?->id,
            'status' => 'pending',
            'ip_address' => request()->ip(),
            'referred_at' => now(),
        ]);

        $this->increment('total_signups');
        $this->updateConversionRate();

        return $referral;
    }

    /**
     * Add commission
     */
    public function addCommission(
        AffiliateReferral $referral,
        Invoice $invoice,
        string $type = 'first_sale'
    ): AffiliateCommission {
        $commissionRate = $type === 'recurring'
            ? $this->tier->recurring_percentage
            : $this->tier->commission_percentage;

        $commissionAmount = round(($invoice->total * $commissionRate) / 100, 2);

        $commission = $this->commissions()->create([
            'referral_id' => $referral->id,
            'invoice_id' => $invoice->id,
            'type' => $type,
            'status' => 'pending',
            'sale_amount' => $invoice->total,
            'commission_percentage' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'currency' => $invoice->currency ?? 'USD',
            'earned_date' => now(),
        ]);

        $this->increment('total_commission_earned', $commissionAmount);
        $this->increment('pending_commission', $commissionAmount);

        if ($type === 'first_sale') {
            $this->increment('total_conversions');
            $this->increment('total_sales', $invoice->total);
            $this->updateConversionRate();
        }

        return $commission;
    }

    /**
     * Update conversion rate
     */
    public function updateConversionRate(): void
    {
        if ($this->total_clicks > 0) {
            $rate = round(($this->total_conversions / $this->total_clicks) * 100, 2);
            $this->update(['conversion_rate' => $rate]);
        }
    }

    /**
     * Approve affiliate
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);

        // Give signup bonus if applicable
        if ($this->tier->signup_bonus > 0) {
            $this->commissions()->create([
                'referral_id' => null,
                'invoice_id' => null,
                'type' => 'bonus',
                'status' => 'approved',
                'sale_amount' => 0,
                'commission_percentage' => 0,
                'commission_amount' => $this->tier->signup_bonus,
                'currency' => 'USD',
                'description' => 'Signup bonus',
                'earned_date' => now(),
                'approved_date' => now(),
            ]);

            $this->increment('total_commission_earned', $this->tier->signup_bonus);
            $this->increment('pending_commission', $this->tier->signup_bonus);
        }
    }

    /**
     * Suspend affiliate
     */
    public function suspend(string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Ban affiliate
     */
    public function ban(string $reason): void
    {
        $this->update([
            'status' => 'banned',
            'notes' => $this->notes . "\n\nBanned: " . $reason,
        ]);
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
     * Get approved commission available for payout
     */
    public function getAvailableForPayoutCommission(): float
    {
        return $this->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->sum('commission_amount');
    }

    /**
     * Check if eligible for payout
     */
    public function isEligibleForPayout(): bool
    {
        $available = $this->getAvailableForPayoutCommission();
        return $available >= $this->tier->minimum_payout;
    }

    /**
     * Request payout
     */
    public function requestPayout(float $amount = null): ?AffiliatePayout
    {
        if (!$this->isEligibleForPayout()) {
            return null;
        }

        $available = $this->getAvailableForPayoutCommission();
        $amount = $amount ?? $available;

        if ($amount > $available) {
            $amount = $available;
        }

        return $this->payouts()->create([
            'payout_number' => AffiliatePayout::generatePayoutNumber(),
            'amount' => $amount,
            'currency' => 'USD',
            'method' => $this->payment_method,
            'status' => 'pending',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
            'requested_at' => now(),
        ]);
    }

    /**
     * Get referral URL
     */
    public function getReferralUrl(string $path = '/'): string
    {
        return url($path . '?ref=' . $this->affiliate_code);
    }

    /**
     * Detect device type
     */
    protected function detectDeviceType(): string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Detect browser
     */
    protected function detectBrowser(): ?string
    {
        $userAgent = request()->userAgent();

        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent)) return 'Safari';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/MSIE|Trident/i', $userAgent)) return 'IE';

        return null;
    }

    /**
     * Get active affiliates
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')->get();
    }

    /**
     * Get affiliate by code
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('affiliate_code', $code)
            ->where('status', 'active')
            ->first();
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
            'banned' => 'gray',
            default => 'gray',
        };
    }
}
