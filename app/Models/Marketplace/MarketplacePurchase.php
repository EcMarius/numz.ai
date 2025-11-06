<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MarketplacePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_item_id',
        'transaction_id',
        'price_paid',
        'platform_fee',
        'creator_earnings',
        'payment_provider',
        'payment_status',
        'refunded_at',
        'refund_reason',
        'license_key',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'creator_earnings' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (! $purchase->license_key) {
                $purchase->license_key = Str::upper(Str::random(16));
            }
        });
    }

    /**
     * The buyer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The item purchased
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    /**
     * The review for this purchase
     */
    public function review(): HasOne
    {
        return $this->hasOne(MarketplaceReview::class, 'purchase_id');
    }

    /**
     * The earnings record for this purchase
     */
    public function earning(): HasOne
    {
        return $this->hasOne(MarketplaceEarning::class, 'purchase_id');
    }

    /**
     * Download logs for this purchase
     */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(MarketplaceDownloadLog::class, 'purchase_id');
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->update(['payment_status' => 'completed']);
    }

    /**
     * Refund this purchase
     */
    public function refund(string $reason): void
    {
        $this->update([
            'payment_status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => $reason,
        ]);

        // Update earning status
        if ($this->earning) {
            $this->earning->update(['status' => 'refunded']);
        }
    }

    /**
     * Check if purchase can be downloaded
     */
    public function canDownload(): bool
    {
        return $this->payment_status === 'completed';
    }
}
