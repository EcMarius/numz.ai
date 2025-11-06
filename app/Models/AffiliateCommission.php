<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'referral_id',
        'invoice_id',
        'type',
        'status',
        'sale_amount',
        'commission_percentage',
        'commission_amount',
        'currency',
        'description',
        'earned_date',
        'approved_date',
        'paid_date',
        'payout_id',
    ];

    protected $casts = [
        'earned_date' => 'date',
        'approved_date' => 'date',
        'paid_date' => 'date',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class);
    }

    /**
     * Approve commission
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_date' => now(),
        ]);
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(AffiliatePayout $payout): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payout_id' => $payout->id,
        ]);
    }

    /**
     * Cancel commission
     */
    public function cancel(): void
    {
        $oldStatus = $this->status;

        $this->update(['status' => 'cancelled']);

        // Update affiliate totals
        if (in_array($oldStatus, ['pending', 'approved'])) {
            $this->affiliate->decrement('total_commission_earned', $this->commission_amount);

            if ($oldStatus === 'pending') {
                $this->affiliate->decrement('pending_commission', $this->commission_amount);
            }
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
