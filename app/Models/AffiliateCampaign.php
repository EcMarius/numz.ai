<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'name',
        'campaign_code',
        'description',
        'landing_page_url',
        'total_clicks',
        'total_conversions',
        'total_sales',
        'is_active',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->total_clicks === 0) {
            return 0;
        }

        return round(($this->total_conversions / $this->total_clicks) * 100, 2);
    }

    /**
     * Get campaign URL
     */
    public function getCampaignUrl(string $path = '/'): string
    {
        return url($path . '?ref=' . $this->affiliate->affiliate_code . '&campaign=' . $this->campaign_code);
    }

    /**
     * Track click
     */
    public function trackClick(): void
    {
        $this->increment('total_clicks');
    }

    /**
     * Track conversion
     */
    public function trackConversion(float $saleAmount): void
    {
        $this->increment('total_conversions');
        $this->increment('total_sales', $saleAmount);
    }

    /**
     * Start campaign
     */
    public function start(): void
    {
        $this->update([
            'is_active' => true,
            'started_at' => now(),
        ]);
    }

    /**
     * End campaign
     */
    public function end(): void
    {
        $this->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);
    }
}
