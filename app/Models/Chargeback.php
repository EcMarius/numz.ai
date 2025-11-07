<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chargeback extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_transaction_id',
        'invoice_id',
        'user_id',
        'chargeback_id',
        'amount',
        'currency',
        'reason_code',
        'reason',
        'status',
        'due_date',
        'evidence',
        'submitted_at',
        'resolved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
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

    /**
     * Submit evidence for chargeback
     */
    public function submitEvidence(string $evidence): void
    {
        $this->update([
            'evidence' => $evidence,
            'submitted_at' => now(),
            'status' => 'under_review',
        ]);
    }

    /**
     * Mark chargeback as won
     */
    public function markAsWon(): void
    {
        // Prevent duplicate resolution
        if (in_array($this->status, ['won', 'lost'])) {
            throw new \Exception('Chargeback already resolved');
        }

        $this->update([
            'status' => 'won',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark chargeback as lost
     */
    public function markAsLost(): void
    {
        // Prevent duplicate resolution
        if (in_array($this->status, ['won', 'lost'])) {
            throw new \Exception('Chargeback already resolved');
        }

        $this->update([
            'status' => 'lost',
            'resolved_at' => now(),
        ]);

        // Create credit note for the chargeback amount if invoice exists
        if ($this->invoice) {
            $this->invoice->createCreditNote(
                $this->amount,
                'refund',
                'Chargeback lost: ' . $this->chargeback_id,
                1 // System user
            );
        }
    }

    /**
     * Scope for pending chargebacks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
