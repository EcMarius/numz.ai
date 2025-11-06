<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'discount_percentage',
        'max_customers',
        'max_services',
        'max_domains',
        'commission_rate',
        'recurring_commission',
        'white_label_enabled',
        'custom_branding',
        'custom_domain',
        'api_access',
        'priority_support',
        'monthly_fee',
        'setup_fee',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'recurring_commission' => 'boolean',
        'white_label_enabled' => 'boolean',
        'custom_branding' => 'boolean',
        'custom_domain' => 'boolean',
        'api_access' => 'boolean',
        'priority_support' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get resellers in this tier
     */
    public function resellers(): HasMany
    {
        return $this->hasMany(Reseller::class);
    }

    /**
     * Check if tier has a feature
     */
    public function hasFeature(string $feature): bool
    {
        return $this->$feature ?? false;
    }

    /**
     * Check if reseller has reached customer limit
     */
    public function isCustomerLimitReached(Reseller $reseller): bool
    {
        if ($this->max_customers === null) {
            return false;
        }

        return $reseller->total_customers >= $this->max_customers;
    }

    /**
     * Check if reseller has reached service limit
     */
    public function isServiceLimitReached(Reseller $reseller): bool
    {
        if ($this->max_services === null) {
            return false;
        }

        return $reseller->total_services >= $this->max_services;
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
