<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'invoice_id',
        'user_id',
        'type',
        'status',
        'order_amount',
        'commission_rate',
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

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(ResellerPayout::class);
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
    public function markAsPaid(ResellerPayout $payout): void
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

        // Update reseller totals
        if (in_array($oldStatus, ['pending', 'approved'])) {
            $this->reseller->decrement('total_commission_earned', $this->commission_amount);

            if ($oldStatus === 'pending') {
                $this->reseller->decrement('pending_commission', $this->commission_amount);
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
