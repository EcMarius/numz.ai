<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_group_id', 'type', 'name', 'slug', 'description',
        'welcome_email', 'hidden', 'show_domain_options', 'stock_control', 'qty',
        'monthly_price', 'quarterly_price', 'semiannually_price', 'annually_price',
        'biennially_price', 'triennially_price', 'setup_fee', 'payment_type',
        'allow_quantity', 'module_name', 'module_config', 'server_group',
        'configoptions', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'hidden' => 'boolean',
        'show_domain_options' => 'boolean',
        'allow_quantity' => 'boolean',
        'is_active' => 'boolean',
        'module_config' => 'array',
        'configoptions' => 'array',
        'monthly_price' => 'decimal:2',
        'quarterly_price' => 'decimal:2',
        'semiannually_price' => 'decimal:2',
        'annually_price' => 'decimal:2',
        'biennially_price' => 'decimal:2',
        'triennially_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
    ];

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function getPriceForCycle(string $cycle): float
    {
        return match($cycle) {
            'monthly' => $this->monthly_price,
            'quarterly' => $this->quarterly_price,
            'semiannually' => $this->semiannually_price,
            'annually' => $this->annually_price,
            'biennially' => $this->biennially_price,
            'triennially' => $this->triennially_price,
            default => 0,
        };
    }
}
