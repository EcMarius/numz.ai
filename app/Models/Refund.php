<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_transaction_id',
        'invoice_id',
        'user_id',
        'refund_id',
        'amount',
        'currency',
        'status',
        'type',
        'reason',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Process the refund
     */
    public function process(int $processedBy): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);

        // Call payment gateway to process refund
        // This would integrate with the actual payment gateway
    }

    /**
     * Mark refund as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);

        // Update invoice status if full refund
        if ($this->type === 'full') {
            $this->invoice->update(['status' => 'refunded']);
        }

        // Create credit note
        $this->invoice->createCreditNote(
            $this->amount,
            'refund',
            $this->reason ?? 'Refund: ' . $this->refund_id,
            $this->processed_by
        );
    }

    /**
     * Scope for pending refunds
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
