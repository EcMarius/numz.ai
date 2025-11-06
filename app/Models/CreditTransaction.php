<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'invoice_id',
        'payment_transaction_id',
        'reference_type',
        'reference_id',
        'admin_id',
        'admin_notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if transaction is a credit (positive amount)
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if transaction is a debit (negative amount)
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . '$' . number_format(abs($this->amount), 2);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'purchase' => 'Credit Purchase',
            'grant' => 'Admin Grant',
            'refund' => 'Refund',
            'payment' => 'Payment',
            'adjustment' => 'Adjustment',
            'bonus' => 'Bonus Credits',
            'expiration' => 'Expiration',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope: Credits only
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * Scope: Debits only
     */
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
