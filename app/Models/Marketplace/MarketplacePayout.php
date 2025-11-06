<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplacePayout extends Model
{
    protected $fillable = [
        'creator_id',
        'amount',
        'earnings_count',
        'method',
        'status',
        'transaction_id',
        'payout_details',
        'failure_reason',
        'requested_at',
        'processed_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'earnings_count' => 'integer',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(MarketplaceEarning::class, 'payout_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Mark payout as processing
     */
    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark payout as completed
     */
    public function markCompleted(string $transactionId): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'completed_at' => now(),
        ]);

        // Update earnings status
        $this->earnings()->update(['status' => 'paid']);
    }

    /**
     * Mark payout as failed
     */
    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        // Return earnings to available status
        $this->earnings()->update(['status' => 'available', 'payout_id' => null]);
    }
}
