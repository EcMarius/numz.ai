<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliatePayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'processed_by',
        'payout_number',
        'amount',
        'currency',
        'method',
        'status',
        'period_start',
        'period_end',
        'payment_details',
        'notes',
        'requested_at',
        'processed_at',
        'completed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payment_details' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'payout_id');
    }

    /**
     * Generate payout number
     */
    public static function generatePayoutNumber(): string
    {
        $prefix = 'APO-';
        $date = now()->format('Ymd');
        $lastPayout = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayout ? ((int) substr($lastPayout->payout_number, -4)) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Process payout
     */
    public function process(int $processedBy): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);
    }

    /**
     * Complete payout
     */
    public function complete(array $paymentDetails = []): void
    {
        // Get approved commissions for this period
        $commissions = $this->affiliate->commissions()
            ->where('status', 'approved')
            ->whereNull('payout_id')
            ->whereBetween('earned_date', [$this->period_start, $this->period_end])
            ->get();

        // Mark commissions as paid
        foreach ($commissions as $commission) {
            $commission->markAsPaid($this);
        }

        // Update affiliate totals
        $this->affiliate->increment('total_commission_paid', $this->amount);
        $this->affiliate->decrement('pending_commission', $this->amount);

        // Update payout status
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'payment_details' => array_merge($this->payment_details ?? [], $paymentDetails),
        ]);
    }

    /**
     * Mark as failed
     */
    public function fail(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Cancel payout
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
