<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'billing_cycle',
        'setup_fee',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HostingProduct::class, 'product_id');
    }

    /**
     * Calculate total price
     */
    public function calculateTotal(): void
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
    }
}
