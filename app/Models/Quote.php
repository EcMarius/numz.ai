<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'created_by',
        'assigned_to',
        'quote_number',
        'title',
        'description',
        'status',
        'type',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'discount_type',
        'discount_value',
        'coupon_id',
        'valid_from',
        'valid_until',
        'validity_days',
        'terms_and_conditions',
        'internal_notes',
        'customer_notes',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'declined_at',
        'expired_at',
        'converted_at',
        'declined_reason',
        'invoice_id',
        'email_sent_count',
        'view_count',
        'follow_up_date',
        'follow_up_sent',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'expired_at' => 'datetime',
        'converted_at' => 'datetime',
        'follow_up_date' => 'datetime',
        'follow_up_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(QuoteActivity::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuoteAttachment::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(QuoteSignature::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Generate quote number
     */
    public static function generateQuoteNumber(): string
    {
        $prefix = 'QT-';
        $date = now()->format('Ymd');
        $lastQuote = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastQuote ? ((int) substr($lastQuote->quote_number, -4)) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');

        $discount = 0;
        if ($this->discount_type === 'percentage') {
            $discount = ($subtotal * $this->discount_value) / 100;
        } elseif ($this->discount_type === 'fixed') {
            $discount = $this->discount_value;
        }

        $afterDiscount = $subtotal - $discount;
        $tax = 0; // Calculate based on tax rules

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $afterDiscount + $tax,
        ]);
    }

    /**
     * Send quote to customer
     */
    public function send(): bool
    {
        try {
            // Send email
            // Mail::to($this->user->email)->send(new QuoteMail($this));

            $this->update([
                'status' => 'sent',
                'sent_at' => now(),
                'email_sent_count' => $this->email_sent_count + 1,
            ]);

            $this->logActivity('sent', 'Quote sent to customer');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mark quote as viewed
     */
    public function markAsViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'viewed_at' => now(),
                'view_count' => 1,
            ]);
        } else {
            $this->increment('view_count');
        }

        $this->logActivity('viewed', 'Quote viewed by customer');
    }

    /**
     * Accept quote
     */
    public function accept(array $signatureData = []): bool
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        if (!empty($signatureData)) {
            $this->signature()->create($signatureData);
        }

        $this->logActivity('accepted', 'Quote accepted by customer');

        return true;
    }

    /**
     * Decline quote
     */
    public function decline(string $reason = null): bool
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'declined_reason' => $reason,
        ]);

        $this->logActivity('declined', 'Quote declined by customer', ['reason' => $reason]);

        return true;
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice(): Invoice
    {
        // Validate quote can be converted
        if ($this->status === 'converted') {
            throw new \Exception('Quote already converted to invoice');
        }

        if ($this->status !== 'accepted') {
            throw new \Exception('Only accepted quotes can be converted');
        }

        if ($this->isExpired()) {
            throw new \Exception('Cannot convert expired quote');
        }

        // Use transaction to prevent duplicate conversion
        return \DB::transaction(function() {
            $invoice = Invoice::create([
                'user_id' => $this->user_id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'status' => 'pending',
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'discount' => $this->discount,
                'total' => $this->total,
                'currency' => $this->currency,
                'due_date' => now()->addDays(14),
            ]);

            // Copy items
            foreach ($this->items as $quoteItem) {
                $invoice->items()->create([
                    'description' => $quoteItem->name,
                    'quantity' => $quoteItem->quantity,
                    'unit_price' => $quoteItem->unit_price,
                    'amount' => $quoteItem->total_price,
                ]);
            }

            $this->update([
                'status' => 'converted',
                'converted_at' => now(),
                'invoice_id' => $invoice->id,
            ]);

            $this->logActivity('converted', 'Quote converted to invoice', ['invoice_id' => $invoice->id]);

            return $invoice;
        });
    }

    /**
     * Check if quote is expired
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if quote is expiring soon
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        return $this->valid_until->isFuture() &&
            $this->valid_until->diffInDays(now()) <= $days;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'sent' => 'info',
            'viewed' => 'warning',
            'accepted' => 'success',
            'declined' => 'danger',
            'expired' => 'gray',
            'converted' => 'success',
            default => 'gray',
        };
    }

    /**
     * Log activity
     */
    public function logActivity(string $action, string $description, array $metadata = []): void
    {
        $this->activities()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get expiring quotes
     */
    public static function getExpiringQuotes(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereIn('status', ['sent', 'viewed'])
            ->where('valid_until', '<=', now()->addDays($days))
            ->where('valid_until', '>', now())
            ->orderBy('valid_until')
            ->get();
    }

    /**
     * Get pending follow-ups
     */
    public static function getPendingFollowUps(): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereIn('status', ['sent', 'viewed'])
            ->where('follow_up_date', '<=', now())
            ->where('follow_up_sent', false)
            ->orderBy('follow_up_date')
            ->get();
    }
}
