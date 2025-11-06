<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'credit_amount',
        'bonus_percentage',
        'sort_order',
        'is_active',
        'is_featured',
        'purchase_limit',
        'available_from',
        'available_until',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'bonus_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'metadata' => 'array',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(CreditPackagePurchase::class);
    }

    /**
     * Calculate total credits (base + bonus)
     */
    public function getTotalCreditsAttribute(): float
    {
        $bonus = ($this->credit_amount * $this->bonus_percentage) / 100;
        return $this->credit_amount + $bonus;
    }

    /**
     * Calculate bonus credits
     */
    public function getBonusCreditsAttribute(): float
    {
        return ($this->credit_amount * $this->bonus_percentage) / 100;
    }

    /**
     * Get value (credits per dollar)
     */
    public function getValueAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }
        return $this->total_credits / $this->price;
    }

    /**
     * Check if package is available
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can purchase
     */
    public function canBePurchasedBy(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($this->purchase_limit) {
            $userPurchases = $this->purchases()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();

            if ($userPurchases >= $this->purchase_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted credits
     */
    public function getFormattedCreditsAttribute(): string
    {
        return '$' . number_format($this->total_credits, 2) . ' credits';
    }

    /**
     * Scope: Active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                    ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                    ->orWhere('available_until', '>=', now());
            });
    }

    /**
     * Scope: Featured packages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }
}
