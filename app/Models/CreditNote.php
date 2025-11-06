<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'credit_note_number',
        'amount',
        'status',
        'type',
        'reason',
        'created_by',
        'approved_by',
        'approved_at',
        'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate credit note number
     */
    public static function generateCreditNoteNumber(): string
    {
        $prefix = 'CN';
        $year = now()->year;
        $month = now()->format('m');

        $lastCreditNote = self::where('credit_note_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastCreditNote) {
            $lastNumber = (int) substr($lastCreditNote->credit_note_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s%04d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Approve credit note
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Apply credit note to invoice or user balance
     */
    public function apply(): void
    {
        if ($this->status !== 'pending') {
            throw new \Exception('Credit note must be pending to apply');
        }

        // Apply credit to user's account balance or reduce invoice amount
        $this->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);

        // Update invoice if applicable
        if ($this->invoice) {
            $this->invoice->applyCredit($this->amount);
        }
    }

    /**
     * Process refund
     */
    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
            'applied_at' => now(),
        ]);
    }
}
