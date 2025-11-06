<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceDownloadLog extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_item_id',
        'purchase_id',
        'version_downloaded',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(MarketplacePurchase::class, 'purchase_id');
    }
}
