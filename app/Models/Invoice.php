<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'parent_invoice_id',
        'approved_by',
        'invoice_number',
        'po_number',
        'department',
        'type',
        'billing_cycle',
        'is_proforma',
        'status',
        'subtotal',
        'tax',
        'tax_inclusive',
        'discount',
        'late_fee',
        'amount_paid',
        'total',
        'currency',
        'locale',
        'due_date',
        'service_start_date',
        'service_end_date',
        'paid_date',
        'approved_at',
        'payment_method',
        'transaction_id',
        'notes',
        'admin_notes',
        'custom_fields',
        'reminder_count',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'service_start_date' => 'date',
        'service_end_date' => 'date',
        'paid_date' => 'datetime',
        'approved_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'is_proforma' => 'boolean',
        'tax_inclusive' => 'boolean',
        'custom_fields' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'parent_invoice_id');
    }

    public function childInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'parent_invoice_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InvoiceReminder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(InvoiceDispute::class);
    }

    public function paymentPlan(): HasMany
    {
        return $this->hasMany(PaymentPlan::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = config('numz.invoice_prefix', 'INV');
        $year = now()->year;
        $month = now()->format('m');

        // Get the last invoice number for this month
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s%s%04d', $prefix, $year, $month, $newNumber);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid' && $this->due_date < now();
    }

    public function markAsPaid(string $paymentMethod, string $transactionId = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
        ]);
    }

    public function addItem(string $description, float $amount, int $quantity = 1, string $itemType = null, int $itemId = null, string $details = null): InvoiceItem
    {
        return $this->items()->create([
            'description' => $description,
            'details' => $details,
            'unit_price' => $amount,
            'amount' => $amount * $quantity,
            'quantity' => $quantity,
            'total' => $amount * $quantity,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $discount = $this->discount ?? 0;
        $taxRate = config('numz.tax_rate', 0); // Percentage
        $tax = ($subtotal - $discount) * ($taxRate / 100);
        $total = $subtotal - $discount + $tax;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    /**
     * Generate PDF for this invoice
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf()
    {
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $this->load(['items', 'user']),
        ])
        ->setPaper('a4', 'portrait')
        ->setOption('defaultFont', 'DejaVu Sans');
    }

    /**
     * Download invoice as PDF
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf()
    {
        return $this->generatePdf()->download("invoice-{$this->invoice_number}.pdf");
    }

    /**
     * Stream invoice PDF in browser
     *
     * @return \Illuminate\Http\Response
     */
    public function streamPdf()
    {
        return $this->generatePdf()->stream("invoice-{$this->invoice_number}.pdf");
    }

    /**
     * Get paid_at attribute (alias for paid_date for template compatibility)
     */
    public function getPaidAtAttribute()
    {
        return $this->paid_date;
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->total - $this->amount_paid);
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->amount_paid > 0 && $this->amount_paid < $this->total;
    }

    /**
     * Apply credit to invoice
     */
    public function applyCredit(float $amount): void
    {
        $newAmountPaid = min($this->total, $this->amount_paid + $amount);
        $this->update(['amount_paid' => $newAmountPaid]);

        // Mark as paid if fully paid
        if ($newAmountPaid >= $this->total) {
            $this->markAsPaid('credit');
        }
    }

    /**
     * Add late fee
     */
    public function addLateFee(float $amount): void
    {
        $this->update([
            'late_fee' => $this->late_fee + $amount,
            'total' => $this->total + $amount,
        ]);
    }

    /**
     * Create reminder for this invoice
     */
    public function createReminder(string $type, int $daysOffset): InvoiceReminder
    {
        $scheduledAt = match($type) {
            'before_due' => $this->due_date->subDays(abs($daysOffset)),
            'on_due' => $this->due_date,
            'overdue' => $this->due_date->addDays($daysOffset),
            default => $this->due_date,
        };

        return $this->reminders()->create([
            'type' => $type,
            'days_offset' => $daysOffset,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Schedule automatic reminders
     */
    public function scheduleReminders(): void
    {
        // 7 days before due
        $this->createReminder('before_due', -7);

        // 3 days before due
        $this->createReminder('before_due', -3);

        // On due date
        $this->createReminder('on_due', 0);

        // 7 days overdue
        $this->createReminder('overdue', 7);

        // 14 days overdue
        $this->createReminder('overdue', 14);
    }

    /**
     * Create credit note for this invoice
     */
    public function createCreditNote(float $amount, string $type, string $reason, int $createdBy): CreditNote
    {
        return $this->creditNotes()->create([
            'user_id' => $this->user_id,
            'credit_note_number' => CreditNote::generateCreditNoteNumber(),
            'amount' => $amount,
            'type' => $type,
            'reason' => $reason,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Create dispute for this invoice
     */
    public function createDispute(string $reason): InvoiceDispute
    {
        return $this->disputes()->create([
            'user_id' => $this->user_id,
            'reason' => $reason,
            'status' => 'open',
        ]);
    }

    /**
     * Convert to proforma invoice
     */
    public function convertToProforma(): self
    {
        $proforma = $this->replicate();
        $proforma->type = 'proforma';
        $proforma->is_proforma = true;
        $proforma->invoice_number = self::generateInvoiceNumber();
        $proforma->parent_invoice_id = $this->id;
        $proforma->save();

        // Replicate items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $proforma->id;
            $newItem->save();
        }

        return $proforma;
    }

    /**
     * Create payment plan for this invoice
     */
    public function createPaymentPlan(int $installments, string $frequency, \Carbon\Carbon $startDate): PaymentPlan
    {
        $paymentPlan = $this->paymentPlan()->create([
            'user_id' => $this->user_id,
            'total_amount' => $this->total,
            'installments' => $installments,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($installments),
        ]);

        $paymentPlan->createInstallments();

        return $paymentPlan;
    }

    /**
     * Increment reminder count
     */
    public function incrementReminderCount(): void
    {
        $this->update([
            'reminder_count' => $this->reminder_count + 1,
            'last_reminder_sent_at' => now(),
        ]);
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'unpaid')
                     ->where('due_date', '<', now());
    }

    /**
     * Scope for proforma invoices
     */
    public function scopeProforma($query)
    {
        return $query->where('type', 'proforma')
                     ->orWhere('is_proforma', true);
    }

    /**
     * Scope for recurring invoices
     */
    public function scopeRecurring($query)
    {
        return $query->where('type', 'recurring');
    }

    /**
     * Scope for partially paid invoices
     */
    public function scopePartiallyPaid($query)
    {
        return $query->where('amount_paid', '>', 0)
                     ->whereColumn('amount_paid', '<', 'total');
    }
}
