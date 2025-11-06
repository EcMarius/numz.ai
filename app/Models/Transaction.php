<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'subscription_id',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_response',
        'status',
        'description',
        'processed_at',
        'failed_at',
        'refunded_at',
        'refund_amount',
        'metadata',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = self::generateTransactionId();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -8));

        $transactionId = "{$prefix}-{$date}-{$random}";

        while (self::where('transaction_id', $transactionId)->exists()) {
            $random = strtoupper(substr(uniqid(), -8));
            $transactionId = "{$prefix}-{$date}-{$random}";
        }

        return $transactionId;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'gateway_response' => $gatewayResponse,
        ]);

        // Update related invoice if exists
        if ($this->invoice_id) {
            $this->invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'description' => $reason,
            'gateway_response' => $gatewayResponse,
        ]);
    }

    /**
     * Process refund
     */
    public function refund(float $amount = null, string $reason = null): void
    {
        $refundAmount = $amount ?? $this->amount;

        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_amount' => $refundAmount,
            'description' => ($this->description ?? '') . "\nRefunded: " . ($reason ?? 'No reason provided'),
        ]);

        // Update invoice status if fully refunded
        if ($this->invoice_id && $refundAmount >= $this->amount) {
            $this->invoice->update([
                'status' => 'refunded',
            ]);
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'pending' => 'warning',
            'processing' => 'info',
            'failed' => 'danger',
            'refunded' => 'gray',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get type badge color
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'payment' => 'success',
            'refund' => 'warning',
            'credit' => 'info',
            'debit' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get recent transactions for a user
     */
    public static function getRecentForUser(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed transactions
     */
    public static function getFailed(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'failed')
            ->orderBy('failed_at', 'desc')
            ->get();
    }

    /**
     * Get total revenue for date range
     */
    public static function getTotalRevenue(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        return self::where('type', 'payment')
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Get total refunds for date range
     */
    public static function getTotalRefunds(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        return self::where('status', 'refunded')
            ->whereBetween('refunded_at', [$startDate, $endDate])
            ->sum('refund_amount');
    }
}
