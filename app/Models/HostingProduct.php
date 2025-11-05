<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'disk_space', 'bandwidth',
        'databases', 'email_accounts', 'ssl_included', 'monthly_price',
        'yearly_price', 'setup_fee', 'module', 'module_config',
        'is_active', 'sort_order'
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'ssl_included' => 'boolean',
        'is_active' => 'boolean',
        'module_config' => 'array',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(HostingService::class);
    }
}
