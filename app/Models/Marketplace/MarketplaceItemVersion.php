<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceItemVersion extends Model
{
    protected $fillable = [
        'marketplace_item_id',
        'version',
        'changelog',
        'file_path',
        'file_size',
        'is_current',
        'downloads_count',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_current' => 'boolean',
        'downloads_count' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    /**
     * Set this version as current
     */
    public function setCurrent(): void
    {
        // Unset all other versions as current
        $this->item->versions()->update(['is_current' => false]);

        // Set this version as current
        $this->update(['is_current' => true]);

        // Update parent item current version
        $this->item->update(['current_version' => $this->version]);
    }
}
