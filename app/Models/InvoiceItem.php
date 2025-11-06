<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'details',
        'unit_price',
        'amount',
        'quantity',
        'total',
        'item_type',
        'item_id',
        'tax_rate',
        'tax_amount',
        'group',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item()
    {
        if (!$this->item_type || !$this->item_id) {
            return null;
        }

        return match ($this->item_type) {
            'service' => HostingService::find($this->item_id),
            'domain' => DomainRegistration::find($this->item_id),
            default => null,
        };
    }
}
