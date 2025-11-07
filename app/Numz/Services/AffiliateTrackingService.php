<?php

namespace App\Numz\Services;

use App\Models\Affiliate;
use App\Models\AffiliateClick;
use App\Models\AffiliateReferral;
use App\Models\AffiliateFraudAlert;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class AffiliateTrackingService
{
    protected const COOKIE_NAME = 'affiliate_ref';

    /**
     * Track affiliate click from URL parameter
     */
    public function trackClick(?string $affiliateCode = null): ?AffiliateClick
    {
        $affiliateCode = $affiliateCode ?? request()->query('ref');

        if (!$affiliateCode) {
            return null;
        }

        $affiliate = Affiliate::getByCode($affiliateCode);

        if (!$affiliate) {
            return null;
        }

        // Track the click
        $click = $affiliate->trackClick([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referrer_url' => request()->header('referer'),
            'landing_page' => request()->fullUrl(),
        ]);

        // Set cookie for attribution
        if (!$affiliate->tier) {
            $cookieLifetime = 30 * 24 * 60; // Default 30 days if tier missing
        } else {
            $cookieLifetime = $affiliate->tier->cookie_lifetime_days * 24 * 60; // Convert to minutes
        }
        Cookie::queue(self::COOKIE_NAME, $affiliateCode, $cookieLifetime);

        // Track campaign if present
        if ($campaignCode = request()->query('campaign')) {
            $campaign = $affiliate->campaigns()->where('campaign_code', $campaignCode)->first();
            $campaign?->trackClick();
        }

        Log::info("Affiliate click tracked", [
            'affiliate_code' => $affiliateCode,
            'affiliate_id' => $affiliate->id,
            'click_id' => $click->id,
        ]);

        return $click;
    }

    /**
     * Get affiliate from cookie
     */
    public function getAffiliateFromCookie(): ?Affiliate
    {
        $affiliateCode = Cookie::get(self::COOKIE_NAME) ?? request()->cookie(self::COOKIE_NAME);

        if (!$affiliateCode) {
            return null;
        }

        return Affiliate::getByCode($affiliateCode);
    }

    /**
     * Track signup/registration
     */
    public function trackSignup(User $user): ?AffiliateReferral
    {
        $affiliate = $this->getAffiliateFromCookie();

        if (!$affiliate) {
            return null;
        }

        // Use transaction to prevent duplicate referral creation
        return \DB::transaction(function() use ($user, $affiliate) {
            // Check if user is already referred (with lock)
            $existingReferral = AffiliateReferral::where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existingReferral) {
                return $existingReferral;
            }

            // Find the click
            $click = $affiliate->clicks()
                ->where('ip_address', request()->ip())
                ->where('converted', false)
                ->orderBy('clicked_at', 'desc')
                ->first();

            // Create referral
            $referral = $affiliate->addReferral($user, $click);

            // Mark click as converted
            $click?->markAsConverted();

            // Check for fraud
            AffiliateFraudAlert::checkSelfReferral($affiliate, $user);

            Log::info("Affiliate signup tracked", [
                'affiliate_code' => $affiliate->affiliate_code,
                'affiliate_id' => $affiliate->id,
                'user_id' => $user->id,
                'referral_id' => $referral->id,
            ]);

            return $referral;
        });
    }

    /**
     * Track conversion (first purchase)
     */
    public function trackConversion(User $user, Invoice $invoice): ?AffiliateReferral
    {
        // Find referral
        $referral = AffiliateReferral::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return null;
        }

        // Confirm referral
        $referral->confirm();

        // Create commission
        $affiliate = $referral->affiliate;
        $affiliate->addCommission($referral, $invoice, 'first_sale');

        // Track campaign conversion
        if ($campaignCode = request()->query('campaign')) {
            $campaign = $affiliate->campaigns()->where('campaign_code', $campaignCode)->first();
            $campaign?->trackConversion($invoice->total);
        }

        // Check for fraud patterns
        AffiliateFraudAlert::checkDuplicateIp($affiliate);

        Log::info("Affiliate conversion tracked", [
            'affiliate_id' => $affiliate->id,
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total,
        ]);

        // Clear cookie after conversion
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));

        return $referral;
    }

    /**
     * Track recurring commission
     */
    public function trackRecurringCommission(User $user, Invoice $invoice): void
    {
        // Find confirmed referral
        $referral = AffiliateReferral::where('user_id', $user->id)
            ->where('status', 'confirmed')
            ->first();

        if (!$referral) {
            return;
        }

        $affiliate = $referral->affiliate;

        // Check if recurring commissions are enabled
        if (!$affiliate->tier->recurring_percentage || $affiliate->tier->recurring_percentage == 0) {
            return;
        }

        // Check commission lifetime
        if ($affiliate->tier->commission_lifetime_months) {
            $cutoffDate = $referral->confirmed_at->copy()->addMonths($affiliate->tier->commission_lifetime_months);

            if (now()->isAfter($cutoffDate)) {
                return; // Commission period expired
            }
        }

        // Create recurring commission
        $affiliate->addCommission($referral, $invoice, 'recurring');

        Log::info("Recurring commission tracked", [
            'affiliate_id' => $affiliate->id,
            'user_id' => $user->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total,
        ]);
    }

    /**
     * Get affiliate stats
     */
    public function getAffiliateStats(Affiliate $affiliate, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $clicks = $affiliate->clicks()
            ->whereBetween('clicked_at', [$startDate, $endDate])
            ->count();

        $signups = $affiliate->signups()
            ->whereBetween('referred_at', [$startDate, $endDate])
            ->count();

        $conversions = $affiliate->signups()
            ->whereBetween('confirmed_at', [$startDate, $endDate])
            ->count();

        $commissions = $affiliate->commissions()
            ->whereBetween('earned_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $totalSales = $commissions->sum('sale_amount');
        $totalCommission = $commissions->sum('commission_amount');

        return [
            'clicks' => $clicks,
            'signups' => $signups,
            'conversions' => $conversions,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'total_sales' => $totalSales,
            'total_commission' => $totalCommission,
            'avg_sale_value' => $conversions > 0 ? round($totalSales / $conversions, 2) : 0,
        ];
    }

    /**
     * Check if affiliate tier should be upgraded
     */
    public function checkTierUpgrade(Affiliate $affiliate): bool
    {
        $recommendedTier = AffiliateTier::getRecommendedTier($affiliate);

        if (!$recommendedTier) {
            return false;
        }

        if ($recommendedTier->level > $affiliate->tier->level) {
            $affiliate->update(['affiliate_tier_id' => $recommendedTier->id]);

            Log::info("Affiliate tier upgraded", [
                'affiliate_id' => $affiliate->id,
                'old_tier' => $affiliate->tier->name,
                'new_tier' => $recommendedTier->name,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Generate referral URL for affiliate
     */
    public function generateReferralUrl(Affiliate $affiliate, string $path = '/', ?string $campaign = null): string
    {
        $url = url($path . '?ref=' . $affiliate->affiliate_code);

        if ($campaign) {
            $url .= '&campaign=' . $campaign;
        }

        return $url;
    }

    /**
     * Clear affiliate cookie
     */
    public function clearAffiliateCookie(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }
}
