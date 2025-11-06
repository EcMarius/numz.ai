<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class ModuleConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_type',
        'module_name',
        'display_name',
        'description',
        'is_enabled',
        'is_available',
        'configuration',
        'credentials',
        'test_mode',
        'sort_order',
        'last_tested_at',
        'test_successful',
        'test_error',
        'capabilities',
        'required_fields',
        'version',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_available' => 'boolean',
        'test_mode' => 'boolean',
        'last_tested_at' => 'datetime',
        'test_successful' => 'boolean',
        'configuration' => 'array',
        'credentials' => 'encrypted:array',
        'capabilities' => 'array',
        'required_fields' => 'array',
    ];

    public function webhooks(): HasMany
    {
        return $this->hasMany(ModuleWebhook::class);
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(ModuleTestResult::class);
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->update(['configuration' => $config]);
    }

    /**
     * Get credential value
     */
    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials, $key, $default);
    }

    /**
     * Set credential value
     */
    public function setCredential(string $key, $value): void
    {
        $credentials = $this->credentials ?? [];
        data_set($credentials, $key, $value);
        $this->update(['credentials' => $credentials]);
    }

    /**
     * Test module connection
     */
    public function testConnection(int $testedBy): bool
    {
        try {
            $startTime = microtime(true);

            // Get the module class
            $moduleClass = $this->getModuleClass();
            if (!$moduleClass) {
                throw new \Exception('Module class not found');
            }

            $module = new $moduleClass($this);
            $result = $module->testConnection();

            $responseTime = microtime(true) - $startTime;

            $this->testResults()->create([
                'tested_by' => $testedBy,
                'test_type' => 'connection',
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? null,
                'details' => $result,
                'response_time' => $responseTime,
            ]);

            $this->update([
                'last_tested_at' => now(),
                'test_successful' => $result['success'] ?? false,
                'test_error' => $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
            ]);

            return $result['success'] ?? false;

        } catch (\Exception $e) {
            $this->testResults()->create([
                'tested_by' => $testedBy,
                'test_type' => 'connection',
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $this->update([
                'last_tested_at' => now(),
                'test_successful' => false,
                'test_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get module class path
     */
    private function getModuleClass(): ?string
    {
        $typeMap = [
            'payment_gateway' => 'App\\Numz\\Modules\\PaymentGateways\\',
            'provisioning' => 'App\\Numz\\Modules\\Provisioning\\',
            'registrar' => 'App\\Numz\\Modules\\Registrars\\',
            'integration' => 'App\\Numz\\Modules\\Integrations\\',
        ];

        $basePath = $typeMap[$this->module_type] ?? null;
        if (!$basePath) {
            return null;
        }

        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->module_name))) . 'Gateway';
        $fullClass = $basePath . $className;

        return class_exists($fullClass) ? $fullClass : null;
    }

    /**
     * Get instance of module
     */
    public function getInstance()
    {
        $moduleClass = $this->getModuleClass();
        if (!$moduleClass) {
            throw new \Exception("Module class not found for {$this->module_name}");
        }

        return new $moduleClass($this);
    }

    /**
     * Check if module has capability
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Scope for enabled modules
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)
                     ->where('is_available', true);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('module_type', $type);
    }

    /**
     * Get all payment gateways
     */
    public static function getPaymentGateways(): \Illuminate\Support\Collection
    {
        return self::ofType('payment_gateway')->enabled()->orderBy('sort_order')->get();
    }

    /**
     * Get all provisioning modules
     */
    public static function getProvisioningModules(): \Illuminate\Support\Collection
    {
        return self::ofType('provisioning')->enabled()->orderBy('sort_order')->get();
    }
}
