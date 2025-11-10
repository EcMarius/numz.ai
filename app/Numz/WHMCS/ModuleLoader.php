<?php

namespace App\Numz\WHMCS;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * WHMCS Module Loader
 *
 * Provides backward compatibility for WHMCS modules including:
 * - Provisioning Modules (servers)
 * - Addon Modules
 * - Payment Gateway Modules
 * - Registrar Modules
 * - Fraud Modules
 * - Notification Provider Modules
 */
class ModuleLoader
{
    /**
     * Supported module types
     */
    const MODULE_TYPES = [
        'servers' => 'provisioning',
        'addons' => 'addon',
        'gateways' => 'payment',
        'registrars' => 'registrar',
        'fraud' => 'fraud',
        'notifications' => 'notification',
        'widgets' => 'widget',
        'mail' => 'mail',
    ];

    /**
     * Loaded modules cache
     */
    protected static array $loadedModules = [];

    /**
     * Module directories
     */
    protected static array $moduleDirectories = [];

    /**
     * Initialize module loader
     */
    public static function init(): void
    {
        self::$moduleDirectories = [
            'servers' => base_path('modules/servers'),
            'addons' => base_path('modules/addons'),
            'gateways' => base_path('modules/gateways'),
            'registrars' => base_path('modules/registrars'),
            'fraud' => base_path('modules/fraud'),
            'notifications' => base_path('modules/notifications'),
            'widgets' => base_path('modules/widgets'),
            'mail' => base_path('modules/mail'),
        ];

        // Create directories if they don't exist
        foreach (self::$moduleDirectories as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    /**
     * Discover all available modules
     */
    public static function discoverModules(string $type = null): array
    {
        $discovered = [];

        $types = $type ? [$type => self::MODULE_TYPES[$type]] : self::MODULE_TYPES;

        foreach ($types as $typeKey => $typeName) {
            $dir = self::$moduleDirectories[$typeKey] ?? null;
            if (!$dir || !File::exists($dir)) {
                continue;
            }

            $modules = File::directories($dir);
            foreach ($modules as $modulePath) {
                $moduleName = basename($modulePath);
                $moduleFile = $modulePath . '/' . $moduleName . '.php';

                if (File::exists($moduleFile)) {
                    $discovered[$typeKey][$moduleName] = [
                        'name' => $moduleName,
                        'type' => $typeName,
                        'path' => $modulePath,
                        'file' => $moduleFile,
                        'metadata' => self::getModuleMetadata($typeKey, $moduleName),
                    ];
                }
            }
        }

        return $discovered;
    }

    /**
     * Load a specific module
     */
    public static function loadModule(string $type, string $moduleName): ?array
    {
        $cacheKey = "whmcs.module.{$type}.{$moduleName}";

        return Cache::remember($cacheKey, 3600, function () use ($type, $moduleName) {
            $dir = self::$moduleDirectories[$type] ?? null;
            if (!$dir) {
                return null;
            }

            $moduleFile = $dir . '/' . $moduleName . '/' . $moduleName . '.php';
            if (!File::exists($moduleFile)) {
                return null;
            }

            // Load the module file
            require_once $moduleFile;

            // Get module metadata
            $metadata = self::getModuleMetadata($type, $moduleName);

            $module = [
                'name' => $moduleName,
                'type' => self::MODULE_TYPES[$type],
                'file' => $moduleFile,
                'metadata' => $metadata,
                'functions' => self::getModuleFunctions($type, $moduleName),
                'loaded' => true,
            ];

            self::$loadedModules[$type][$moduleName] = $module;

            return $module;
        });
    }

    /**
     * Get module metadata
     */
    protected static function getModuleMetadata(string $type, string $moduleName): array
    {
        $functionName = $moduleName . '_MetaData';

        if (function_exists($functionName)) {
            return call_user_func($functionName);
        }

        return [];
    }

    /**
     * Get module configuration options
     */
    public static function getConfigOptions(string $type, string $moduleName): array
    {
        self::loadModule($type, $moduleName);

        $functionName = $moduleName . '_ConfigOptions';

        if (function_exists($functionName)) {
            return call_user_func($functionName);
        }

        return [];
    }

    /**
     * Get all module functions
     */
    protected static function getModuleFunctions(string $type, string $moduleName): array
    {
        $functions = [];
        $prefix = $moduleName . '_';

        // Get all functions starting with module name
        $allFunctions = get_defined_functions()['user'] ?? [];

        foreach ($allFunctions as $function) {
            if (str_starts_with($function, $prefix)) {
                $functionType = str_replace($prefix, '', $function);
                $functions[$functionType] = $function;
            }
        }

        return $functions;
    }

    /**
     * Call a module function
     */
    public static function callModuleFunction(
        string $type,
        string $moduleName,
        string $function,
        array $params = []
    ): mixed {
        $module = self::loadModule($type, $moduleName);

        if (!$module) {
            throw new \Exception("Module {$moduleName} not found");
        }

        $functionName = $moduleName . '_' . $function;

        if (!function_exists($functionName)) {
            throw new \Exception("Function {$function} not found in module {$moduleName}");
        }

        try {
            return call_user_func($functionName, $params);
        } catch (\Exception $e) {
            Log::error("WHMCS Module Error: {$moduleName}::{$function}", [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get active provisioning modules
     */
    public static function getActiveProvisioningModules(): array
    {
        return Cache::remember('whmcs.active.servers', 600, function () {
            // Get from database
            $products = \App\Models\Product::whereNotNull('server_module')
                ->distinct()
                ->pluck('server_module')
                ->toArray();

            $modules = [];
            foreach ($products as $moduleName) {
                $module = self::loadModule('servers', $moduleName);
                if ($module) {
                    $modules[$moduleName] = $module;
                }
            }

            return $modules;
        });
    }

    /**
     * Get active addon modules
     */
    public static function getActiveAddonModules(): array
    {
        return Cache::remember('whmcs.active.addons', 600, function () {
            // Get from configuration
            $addons = config('whmcs.active_addons', []);

            $modules = [];
            foreach ($addons as $moduleName) {
                $module = self::loadModule('addons', $moduleName);
                if ($module) {
                    $modules[$moduleName] = $module;
                }
            }

            return $modules;
        });
    }

    /**
     * Get active payment gateways
     */
    public static function getActiveGateways(): array
    {
        return Cache::remember('whmcs.active.gateways', 600, function () {
            $gateways = \App\Models\PaymentGateway::where('is_active', true)
                ->pluck('gateway_name')
                ->toArray();

            $modules = [];
            foreach ($gateways as $gatewayName) {
                $module = self::loadModule('gateways', $gatewayName);
                if ($module) {
                    $modules[$gatewayName] = $module;
                }
            }

            return $modules;
        });
    }

    /**
     * Validate module structure
     */
    public static function validateModule(string $type, string $moduleName): array
    {
        $errors = [];
        $warnings = [];

        $module = self::loadModule($type, $moduleName);

        if (!$module) {
            $errors[] = "Module file not found";
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // Check required functions based on module type
        $requiredFunctions = self::getRequiredFunctions($type);

        foreach ($requiredFunctions as $function) {
            if (!isset($module['functions'][$function])) {
                $errors[] = "Required function '{$function}' not found";
            }
        }

        // Check metadata
        if (empty($module['metadata'])) {
            $warnings[] = "Module metadata not found (optional but recommended)";
        }

        // Check configuration options
        $config = self::getConfigOptions($type, $moduleName);
        if (empty($config) && $type === 'servers') {
            $warnings[] = "No configuration options defined";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get required functions for module type
     */
    protected static function getRequiredFunctions(string $type): array
    {
        return match ($type) {
            'servers' => ['ConfigOptions', 'CreateAccount', 'SuspendAccount', 'UnsuspendAccount', 'TerminateAccount'],
            'gateways' => ['MetaData', 'Config'],
            'addons' => ['config', 'activate', 'deactivate', 'output'],
            'registrars' => ['getConfigArray', 'RegisterDomain', 'TransferDomain', 'RenewDomain'],
            default => [],
        };
    }

    /**
     * Clear module cache
     */
    public static function clearCache(string $type = null, string $moduleName = null): void
    {
        if ($type && $moduleName) {
            Cache::forget("whmcs.module.{$type}.{$moduleName}");
        } elseif ($type) {
            Cache::forget("whmcs.active.{$type}");
        } else {
            Cache::flush(); // Clear all cache (use with caution)
        }
    }
}
