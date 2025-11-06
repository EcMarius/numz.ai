<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainRegistrar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_enabled',
        'is_available',
        'configuration',
        'credentials',
        'supported_tlds',
        'pricing',
        'capabilities',
        'test_mode',
        'last_sync_at',
        'domain_count',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_available' => 'boolean',
        'test_mode' => 'boolean',
        'configuration' => 'array',
        'credentials' => 'encrypted:array',
        'supported_tlds' => 'array',
        'pricing' => 'array',
        'capabilities' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get domains
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get domain pricing
     */
    public function domainPricing(): HasMany
    {
        return $this->hasMany(DomainPricing::class);
    }

    /**
     * Get incoming transfers
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(DomainTransfer::class, 'to_registrar_id');
    }

    /**
     * Get outgoing transfers
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(DomainTransfer::class, 'from_registrar_id');
    }

    /**
     * Check if registrar supports a TLD
     */
    public function supportsTld(string $tld): bool
    {
        if (empty($this->supported_tlds)) {
            return false;
        }

        $tld = strtolower($tld);
        if (!str_starts_with($tld, '.')) {
            $tld = '.' . $tld;
        }

        return in_array($tld, $this->supported_tlds);
    }

    /**
     * Check if registrar has a capability
     */
    public function hasCapability(string $capability): bool
    {
        if (empty($this->capabilities)) {
            return false;
        }

        return in_array($capability, $this->capabilities);
    }

    /**
     * Get registrar module instance
     */
    public function getModuleInstance(): mixed
    {
        $className = "App\\Numz\\Modules\\DomainRegistrars\\{$this->slug}Registrar";

        if (!class_exists($className)) {
            throw new \Exception("Registrar module {$className} not found");
        }

        return new $className($this);
    }

    /**
     * Test connection to registrar
     */
    public function testConnection(): array
    {
        try {
            $module = $this->getModuleInstance();
            return $module->testConnection();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync pricing from registrar
     */
    public function syncPricing(): array
    {
        try {
            $module = $this->getModuleInstance();
            $result = $module->syncPricing();

            $this->update(['last_sync_at' => now()]);

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get enabled registrars
     */
    public static function getEnabled(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_enabled', true)
            ->where('is_available', true)
            ->get();
    }

    /**
     * Get registrar by slug
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Get price for TLD
     */
    public function getPriceForTld(string $tld, string $action = 'register', int $years = 1): ?float
    {
        $pricing = $this->domainPricing()
            ->where('tld', $tld)
            ->where('is_active', true)
            ->first();

        if (!$pricing) {
            return null;
        }

        $priceField = match($action) {
            'register' => "register_price_{$years}y",
            'renew' => "renew_price_{$years}y",
            'transfer' => 'transfer_price',
            default => null,
        };

        return $priceField ? $pricing->$priceField : null;
    }
}
