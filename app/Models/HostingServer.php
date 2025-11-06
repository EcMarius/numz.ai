<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingServer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'port',
        'type',
        'username',
        'access_key',
        'ssl_enabled',
        'max_accounts',
        'active_accounts',
        'is_active',
        'nameserver1',
        'nameserver2',
        'nameserver3',
        'nameserver4',
    ];

    protected $casts = [
        'port' => 'integer',
        'ssl_enabled' => 'boolean',
        'max_accounts' => 'integer',
        'active_accounts' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all services hosted on this server
     */
    public function services(): HasMany
    {
        return $this->hasMany(HostingService::class, 'server_id');
    }

    /**
     * Get active services
     */
    public function activeServices(): HasMany
    {
        return $this->services()->where('status', 'active');
    }

    /**
     * Check if server has capacity
     */
    public function hasCapacity(): bool
    {
        return $this->active_accounts < $this->max_accounts;
    }

    /**
     * Increment active accounts
     */
    public function incrementAccounts(): void
    {
        $this->increment('active_accounts');
    }

    /**
     * Decrement active accounts
     */
    public function decrementAccounts(): void
    {
        $this->decrement('active_accounts');
    }

    /**
     * Get provisioning module class
     */
    public function getProvisioningModule()
    {
        $moduleClass = "App\\Numz\\Modules\\Provisioning\\" . ucfirst($this->type) . "Provisioning";
        
        if (!class_exists($moduleClass)) {
            throw new \Exception("Provisioning module not found: {$moduleClass}");
        }

        $module = new $moduleClass();
        $module->initialize([
            'hostname' => $this->hostname,
            'username' => $this->username,
            'api_token' => $this->access_key,
            'use_ssl' => $this->ssl_enabled,
            'port' => $this->port,
        ]);

        return $module;
    }

    /**
     * Test connection to server
     */
    public function testConnection(): array
    {
        try {
            $module = $this->getProvisioningModule();
            return $module->testConnection();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
