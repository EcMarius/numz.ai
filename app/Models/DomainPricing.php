<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainPricing extends Model
{
    use HasFactory;

    protected $table = 'domain_pricing';

    protected $fillable = [
        'domain_registrar_id',
        'tld',
        'currency',
        'register_price_1y',
        'register_price_2y',
        'register_price_3y',
        'register_price_5y',
        'register_price_10y',
        'renew_price_1y',
        'renew_price_2y',
        'renew_price_3y',
        'renew_price_5y',
        'renew_price_10y',
        'transfer_price',
        'whois_privacy_price',
        'supports_premium',
        'is_active',
        'last_updated_at',
    ];

    protected $casts = [
        'supports_premium' => 'boolean',
        'is_active' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(DomainRegistrar::class, 'domain_registrar_id');
    }

    /**
     * Get pricing for a specific period
     */
    public function getRegistrationPrice(int $years = 1): ?float
    {
        $field = "register_price_{$years}y";
        return $this->$field;
    }

    /**
     * Get renewal pricing for a specific period
     */
    public function getRenewalPrice(int $years = 1): ?float
    {
        $field = "renew_price_{$years}y";
        return $this->$field;
    }
}
