<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'points_per_dollar',
        'minimum_spend',
        'tier_rules',
        'redemption_rules',
        'points_expiry_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'minimum_spend' => 'decimal:2',
        'tier_rules' => 'array',
        'redemption_rules' => 'array',
    ];

    public function userPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Calculate points earned for a purchase amount
     */
    public function calculatePoints(float $amount): int
    {
        if ($amount < $this->minimum_spend) {
            return 0;
        }

        return (int) floor($amount * $this->points_per_dollar);
    }

    /**
     * Get tier for points amount
     */
    public function getTierForPoints(int $points): ?string
    {
        if (!$this->tier_rules) {
            return null;
        }

        $tier = null;
        foreach ($this->tier_rules as $tierName => $tierData) {
            if ($points >= ($tierData['min_points'] ?? 0)) {
                $tier = $tierName;
            }
        }

        return $tier;
    }

    /**
     * Get active loyalty program
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
