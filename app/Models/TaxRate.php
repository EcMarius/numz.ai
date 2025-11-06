<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'type',
        'country',
        'state',
        'zip_codes',
        'applies_to_hosting',
        'applies_to_domains',
        'applies_to_addons',
        'applies_to_setup_fees',
        'is_vat',
        'reverse_charge',
        'require_vat_number',
        'priority',
        'is_active',
        'is_compound',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'zip_codes' => 'array',
        'applies_to_hosting' => 'boolean',
        'applies_to_domains' => 'boolean',
        'applies_to_addons' => 'boolean',
        'applies_to_setup_fees' => 'boolean',
        'is_vat' => 'boolean',
        'reverse_charge' => 'boolean',
        'require_vat_number' => 'boolean',
        'is_active' => 'boolean',
        'is_compound' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'rate' => 'decimal:2',
    ];

    /**
     * Check if tax rate is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if tax applies to a specific location
     */
    public function appliesToLocation(?string $country, ?string $state = null, ?string $zipCode = null): bool
    {
        // If no country restriction, applies globally
        if (!$this->country) {
            return true;
        }

        // Check country match
        if (strtoupper($country) !== strtoupper($this->country)) {
            return false;
        }

        // Check state/province if specified
        if ($this->state && strtoupper($state) !== strtoupper($this->state)) {
            return false;
        }

        // Check zip/postal code if specified
        if ($this->zip_codes && $zipCode) {
            if (!in_array($zipCode, $this->zip_codes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if tax applies to product type
     */
    public function appliesToProductType(string $type): bool
    {
        return match ($type) {
            'hosting' => $this->applies_to_hosting,
            'domain' => $this->applies_to_domains,
            'addon' => $this->applies_to_addons,
            'setup_fee' => $this->applies_to_setup_fees,
            default => true,
        };
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(float $amount): float
    {
        if ($this->type === 'percent') {
            return round(($amount * $this->rate) / 100, 2);
        }

        if ($this->type === 'fixed') {
            return $this->rate;
        }

        return 0;
    }

    /**
     * Get formatted rate
     */
    public function getFormattedRateAttribute(): string
    {
        if ($this->type === 'percent') {
            return $this->rate . '%';
        }

        return '$' . number_format($this->rate, 2);
    }

    /**
     * Scope: Active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope: By priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('rate', 'desc');
    }

    /**
     * Scope: For location
     */
    public function scopeForLocation($query, ?string $country, ?string $state = null)
    {
        return $query->where(function ($q) use ($country, $state) {
            $q->whereNull('country')
                ->orWhere(function ($q2) use ($country, $state) {
                    $q2->where('country', strtoupper($country))
                        ->where(function ($q3) use ($state) {
                            $q3->whereNull('state')
                                ->orWhere('state', strtoupper($state));
                        });
                });
        });
    }
}
