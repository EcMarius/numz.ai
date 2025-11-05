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
        'invoice_number',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'due_date',
        'paid_date',
        'payment_method',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
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
}
