<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'commission_percentage',
        'recurring_percentage',
        'cookie_lifetime_days',
        'commission_lifetime_months',
        'min_referrals',
        'min_sales',
        'signup_bonus',
        'monthly_bonus_threshold',
        'monthly_bonus_amount',
        'minimum_payout',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get affiliates in this tier
     */
    public function affiliates(): HasMany
    {
        return $this->hasMany(Affiliate::class);
    }

    /**
     * Check if affiliate qualifies for this tier
     */
    public function qualifiesForTier(Affiliate $affiliate): bool
    {
        if ($affiliate->total_conversions < $this->min_referrals) {
            return false;
        }

        if ($affiliate->total_sales < $this->min_sales) {
            return false;
        }

        return true;
    }

    /**
     * Get recommended tier for affiliate
     */
    public static function getRecommendedTier(Affiliate $affiliate): ?self
    {
        return self::where('is_active', true)
            ->orderBy('level', 'desc')
            ->get()
            ->first(fn($tier) => $tier->qualifiesForTier($affiliate));
    }

    /**
     * Get active tiers
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('level')
            ->get();
    }

    /**
     * Get tier by slug
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
