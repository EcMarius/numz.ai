<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'invoice_number', 'date', 'due_date', 'date_paid',
        'subtotal', 'credit', 'tax', 'tax2', 'total',
        'tax_rate', 'tax_rate2', 'status', 'payment_method', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'date_paid' => 'date',
        'subtotal' => 'decimal:2',
        'credit' => 'decimal:2',
        'tax' => 'decimal:2',
        'tax2' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid' && $this->due_date < now();
    }
}
