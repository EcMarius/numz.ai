<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceEarning extends Model
{
    protected $fillable = [
        'creator_id',
        'marketplace_item_id',
        'purchase_id',
        'amount',
        'platform_fee',
        'status',
        'available_at',
        'payout_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'available_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Earnings become available after 7 days
        static::creating(function ($earning) {
            if (! $earning->available_at) {
                $earning->available_at = now()->addDays(7);
                $earning->status = 'pending';
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(MarketplacePurchase::class, 'purchase_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(MarketplacePayout::class, 'payout_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
            ->where('available_at', '<=', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Mark earning as available for payout
     */
    public function markAvailable(): void
    {
        if ($this->status === 'pending' && now()->gte($this->available_at)) {
            $this->update(['status' => 'available']);
        }
    }
}
