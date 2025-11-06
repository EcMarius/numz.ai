<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get all items in this category
     */
    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceItem::class, 'category_id');
    }

    /**
     * Get approved items in this category
     */
    public function approvedItems(): HasMany
    {
        return $this->items()->where('status', 'approved')->where('is_active', true);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
