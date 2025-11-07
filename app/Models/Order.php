<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_number',
        'order_type',
        'status',
        'billing_cycle',
        'quantity',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'domain',
        'configuration',
        'notes',
        'setup_fee',
        'activation_date',
        'next_due_date',
        'next_invoice_date',
        'termination_date',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'configuration' => 'array',
        'metadata' => 'array',
        'activation_date' => 'datetime',
        'next_due_date' => 'datetime',
        'next_invoice_date' => 'datetime',
        'termination_date' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        $orderNumber = "{$prefix}-{$date}-{$random}";

        // Ensure uniqueness
        while (self::where('order_number', $orderNumber)->exists()) {
            $random = strtoupper(substr(uniqid(), -6));
            $orderNumber = "{$prefix}-{$date}-{$random}";
        }

        return $orderNumber;
    }

    /**
     * Activate the order
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activation_date' => now(),
            'next_due_date' => $this->calculateNextDueDate(),
            'next_invoice_date' => $this->calculateNextInvoiceDate(),
        ]);
    }

    /**
     * Suspend the order
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'notes' => ($this->notes ?? '') . "\nSuspended: " . ($reason ?? 'Payment overdue') . ' at ' . now(),
        ]);
    }

    /**
     * Unsuspend the order
     */
    public function unsuspend(): void
    {
        $this->update([
            'status' => 'active',
            'notes' => ($this->notes ?? '') . "\nUnsuspended at " . now(),
        ]);
    }

    /**
     * Cancel the order
     */
    public function cancel(string $reason = 'customer_request'): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'termination_date' => now(),
        ]);
    }

    /**
     * Terminate the order
     */
    public function terminate(): void
    {
        $this->update([
            'status' => 'terminated',
            'termination_date' => now(),
        ]);
    }

    /**
     * Calculate next due date based on billing cycle
     */
    public function calculateNextDueDate(): \Carbon\Carbon
    {
        $baseDate = $this->next_due_date ?? $this->activation_date ?? now();

        return match($this->billing_cycle) {
            'monthly' => $baseDate->copy()->addMonth(),
            'quarterly' => $baseDate->copy()->addMonths(3),
            'semi_annually' => $baseDate->copy()->addMonths(6),
            'annually' => $baseDate->copy()->addYear(),
            'biennially' => $baseDate->copy()->addYears(2),
            'triennially' => $baseDate->copy()->addYears(3),
            'free' => $baseDate->copy()->addYears(100),
            'one_time' => $baseDate,
            default => $baseDate->copy()->addMonth(),
        };
    }

    /**
     * Calculate next invoice date (typically 7-14 days before due date)
     */
    public function calculateNextInvoiceDate(): \Carbon\Carbon
    {
        $nextDueDate = $this->calculateNextDueDate();
        $daysBeforeDue = config('billing.invoice_generation_days_before_due', 14);

        return $nextDueDate->copy()->subDays($daysBeforeDue);
    }

    /**
     * Renew the order
     */
    public function renew(): void
    {
        $this->update([
            'next_due_date' => $this->calculateNextDueDate(),
            'next_invoice_date' => $this->calculateNextInvoiceDate(),
        ]);

        // Create renewal invoice
        $this->createRenewalInvoice();
    }

    /**
     * Create renewal invoice
     */
    public function createRenewalInvoice(): Invoice
    {
        // Use transaction to prevent duplicate renewal invoices
        return \DB::transaction(function() {
            // Check if renewal invoice already exists
            $existingInvoice = Invoice::where('order_id', $this->id)
                ->where('invoice_type', 'renewal')
                ->where('status', 'pending')
                ->where('due_date', $this->next_due_date)
                ->lockForUpdate()
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $productName = $this->product ? $this->product->name : 'Product (Deleted)';

            return Invoice::create([
                'user_id' => $this->user_id,
                'order_id' => $this->id,
                'invoice_type' => 'renewal',
                'status' => 'pending',
                'subtotal' => $this->subtotal,
                'tax' => $this->tax,
                'discount' => 0,
                'total' => $this->total,
                'due_date' => $this->next_due_date,
                'items' => [[
                    'description' => "{$productName} - {$this->billing_cycle} renewal",
                    'quantity' => $this->quantity,
                    'unit_price' => $this->subtotal,
                    'total' => $this->subtotal,
                ]],
            ]);
        });
    }

    /**
     * Upgrade to a different product
     */
    public function upgradeTo(Product $newProduct, array $options = []): self
    {
        // Calculate prorated credit
        $daysRemaining = now()->diffInDays($this->next_due_date);
        $totalDays = $this->activation_date->diffInDays($this->next_due_date);
        $proratedCredit = $daysRemaining > 0 ? ($this->total / $totalDays) * $daysRemaining : 0;

        // Create new order
        $newOrder = self::create([
            'user_id' => $this->user_id,
            'product_id' => $newProduct->id,
            'order_type' => 'upgrade',
            'status' => 'pending',
            'billing_cycle' => $options['billing_cycle'] ?? $this->billing_cycle,
            'quantity' => $options['quantity'] ?? $this->quantity,
            'subtotal' => $newProduct->price - $proratedCredit,
            'tax' => ($newProduct->price - $proratedCredit) * ($this->tax / $this->subtotal),
            'total' => $newProduct->price - $proratedCredit + (($newProduct->price - $proratedCredit) * ($this->tax / $this->subtotal)),
            'domain' => $this->domain,
            'configuration' => $options['configuration'] ?? $this->configuration,
            'notes' => "Upgraded from Order #{$this->order_number}",
        ]);

        // Cancel old order
        $this->cancel('upgraded');

        return $newOrder;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'suspended' => 'warning',
            'cancelled' => 'danger',
            'terminated' => 'danger',
            'completed' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get billing cycle display name
     */
    public function getBillingCycleDisplayAttribute(): string
    {
        return match($this->billing_cycle) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi_annually' => 'Semi-Annually',
            'annually' => 'Annually',
            'biennially' => 'Biennially',
            'triennially' => 'Triennially',
            'free' => 'Free',
            'one_time' => 'One Time',
            default => ucfirst($this->billing_cycle),
        };
    }

    /**
     * Check if order is overdue
     */
    public function isOverdue(): bool
    {
        return $this->next_due_date &&
               $this->next_due_date->isPast() &&
               in_array($this->status, ['active', 'pending']);
    }

    /**
     * Get orders due for renewal
     */
    public static function getDueForRenewal(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', 'active')
            ->where('next_invoice_date', '<=', now())
            ->whereDoesntHave('invoices', function ($query) {
                $query->where('invoice_type', 'renewal')
                      ->where('status', 'pending');
            })
            ->get();
    }

    /**
     * Get overdue orders
     */
    public static function getOverdue(): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereIn('status', ['active', 'pending'])
            ->where('next_due_date', '<', now())
            ->get();
    }
}
