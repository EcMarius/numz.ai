<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'category',
        'sku',
        'price',
        'setup_fee',
        'billing_cycles',
        'stock_quantity',
        'stock_status',
        'is_active',
        'is_featured',
        'requires_domain',
        'auto_setup',
        'welcome_email_template_id',
        'server_id',
        'configuration_options',
        'pricing_tiers',
        'features',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'billing_cycles' => 'array',
        'configuration_options' => 'array',
        'pricing_tiers' => 'array',
        'features' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'requires_domain' => 'boolean',
        'auto_setup' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (!$product->slug) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ProductMetric::class);
    }

    /**
     * Get price for specific billing cycle
     */
    public function getPriceForCycle(string $billingCycle): float
    {
        if (!$this->pricing_tiers || !isset($this->pricing_tiers[$billingCycle])) {
            return $this->price;
        }

        return $this->pricing_tiers[$billingCycle];
    }

    /**
     * Check if product is in stock
     */
    public function inStock(): bool
    {
        return $this->stock_status === 'in_stock' &&
               ($this->stock_quantity === null || $this->stock_quantity > 0);
    }

    /**
     * Decrease stock
     */
    public function decreaseStock(int $quantity = 1): void
    {
        if ($this->stock_quantity !== null) {
            $newQuantity = max(0, $this->stock_quantity - $quantity);

            $this->update([
                'stock_quantity' => $newQuantity,
                'stock_status' => $newQuantity > 0 ? 'in_stock' : 'out_of_stock',
            ]);
        }
    }

    /**
     * Increase stock
     */
    public function increaseStock(int $quantity = 1): void
    {
        if ($this->stock_quantity !== null) {
            $this->update([
                'stock_quantity' => $this->stock_quantity + $quantity,
                'stock_status' => 'in_stock',
            ]);
        }
    }

    /**
     * Get available billing cycles
     */
    public function getAvailableBillingCycles(): array
    {
        $allCycles = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi_annually' => 'Semi-Annually',
            'annually' => 'Annually',
            'biennially' => 'Biennially',
            'triennially' => 'Triennially',
            'one_time' => 'One Time',
            'free' => 'Free',
        ];

        if (!$this->billing_cycles) {
            return $allCycles;
        }

        return array_intersect_key($allCycles, array_flip($this->billing_cycles));
    }

    /**
     * Get stock status badge color
     */
    public function getStockStatusColorAttribute(): string
    {
        return match($this->stock_status) {
            'in_stock' => 'success',
            'low_stock' => 'warning',
            'out_of_stock' => 'danger',
            'on_backorder' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get type badge color
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'hosting' => 'primary',
            'domain' => 'success',
            'ssl' => 'warning',
            'addon' => 'info',
            'service' => 'secondary',
            default => 'gray',
        };
    }

    /**
     * Get active products
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get featured products
     */
    public static function getFeatured(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * Get products by category
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('category', $category)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get products by type
     */
    public static function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where('type', $type)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search products
     */
    public static function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->get();
    }
}
