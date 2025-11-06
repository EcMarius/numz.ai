<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'product_id',
        'monthly_price',
        'quarterly_price',
        'semi_annual_price',
        'annual_price',
        'biennial_price',
        'triennial_price',
        'setup_fee',
        'cost_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HostingProduct::class, 'product_id');
    }

    /**
     * Get price for billing cycle
     */
    public function getPriceForCycle(string $cycle): ?float
    {
        return match($cycle) {
            'monthly' => $this->monthly_price,
            'quarterly' => $this->quarterly_price,
            'semi_annual' => $this->semi_annual_price,
            'annual' => $this->annual_price,
            'biennial' => $this->biennial_price,
            'triennial' => $this->triennial_price,
            default => null,
        };
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMargin(string $cycle = 'monthly'): ?float
    {
        $price = $this->getPriceForCycle($cycle);

        if (!$price || !$this->cost_price) {
            return null;
        }

        return round((($price - $this->cost_price) / $price) * 100, 2);
    }
}
