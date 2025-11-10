<?php

namespace App\Numz\WHMCS;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * WHMCS Hook Manager
 *
 * Provides full compatibility with WHMCS hook system:
 * - Hook registration and execution
 * - Priority-based execution
 * - Module hooks
 * - Action hooks
 * - Point-in-time hooks
 */
class HookManager
{
    /**
     * Registered hooks
     */
    protected static array $hooks = [];

    /**
     * Hook execution history
     */
    protected static array $executionHistory = [];

    /**
     * Hook directories
     */
    protected static array $hookDirectories = [];

    /**
     * Initialize hook manager
     */
    public static function init(): void
    {
        self::$hookDirectories = [
            base_path('includes/hooks'),
            base_path('modules/addons'),
            base_path('modules/servers'),
            base_path('modules/gateways'),
            base_path('modules/registrars'),
        ];

        // Create hooks directory if it doesn't exist
        if (!File::exists(base_path('includes/hooks'))) {
            File::makeDirectory(base_path('includes/hooks'), 0755, true);
        }

        // Load all hooks
        self::loadHooks();
    }

    /**
     * Add a hook (WHMCS-style)
     *
     * @param string $hookPoint Hook point identifier
     * @param int $priority Execution priority (lower = earlier)
     * @param callable|string $function Function to execute
     */
    public static function add_hook(string $hookPoint, int $priority, $function): void
    {
        if (!isset(self::$hooks[$hookPoint])) {
            self::$hooks[$hookPoint] = [];
        }

        if (!isset(self::$hooks[$hookPoint][$priority])) {
            self::$hooks[$hookPoint][$priority] = [];
        }

        self::$hooks[$hookPoint][$priority][] = [
            'function' => $function,
            'registered_at' => now(),
        ];

        // Sort by priority
        ksort(self::$hooks[$hookPoint]);
    }

    /**
     * Run hooks for a specific point
     *
     * @param string $hookPoint Hook point identifier
     * @param array $params Parameters to pass to hook functions
     * @return array Results from all hook executions
     */
    public static function run(string $hookPoint, array $params = []): array
    {
        $results = [];

        if (!isset(self::$hooks[$hookPoint])) {
            return $results;
        }

        foreach (self::$hooks[$hookPoint] as $priority => $hooks) {
            foreach ($hooks as $hook) {
                try {
                    $function = $hook['function'];

                    // Execute the hook
                    if (is_callable($function)) {
                        $result = call_user_func_array($function, [$params]);
                    } elseif (is_string($function) && function_exists($function)) {
                        $result = call_user_func($function, $params);
                    } else {
                        continue;
                    }

                    // Store execution history
                    self::$executionHistory[] = [
                        'hook_point' => $hookPoint,
                        'priority' => $priority,
                        'function' => is_string($function) ? $function : 'Closure',
                        'executed_at' => now(),
                        'params' => $params,
                        'result' => $result,
                    ];

                    $results[] = $result;
                } catch (\Exception $e) {
                    Log::error("Hook execution failed: {$hookPoint}", [
                        'error' => $e->getMessage(),
                        'function' => is_string($function) ? $function : 'Closure',
                        'params' => $params,
                    ]);

                    $results[] = [
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Load all hook files
     */
    protected static function loadHooks(): void
    {
        // Load hooks from includes/hooks directory
        $hooksDir = base_path('includes/hooks');

        if (File::exists($hooksDir)) {
            $hookFiles = File::glob($hooksDir . '/*.php');

            foreach ($hookFiles as $hookFile) {
                require_once $hookFile;
            }
        }

        // Load module hooks
        self::loadModuleHooks();
    }

    /**
     * Load hooks from modules
     */
    protected static function loadModuleHooks(): void
    {
        $moduleTypes = ['addons', 'servers', 'gateways', 'registrars'];

        foreach ($moduleTypes as $type) {
            $modulesDir = base_path("modules/{$type}");

            if (!File::exists($modulesDir)) {
                continue;
            }

            $modules = File::directories($modulesDir);

            foreach ($modules as $moduleDir) {
                $hookFile = $moduleDir . '/hooks.php';

                if (File::exists($hookFile)) {
                    require_once $hookFile;
                }
            }
        }
    }

    /**
     * Get registered hooks for a specific point
     */
    public static function getHooks(string $hookPoint = null): array
    {
        if ($hookPoint) {
            return self::$hooks[$hookPoint] ?? [];
        }

        return self::$hooks;
    }

    /**
     * Get hook execution history
     */
    public static function getExecutionHistory(string $hookPoint = null): array
    {
        if ($hookPoint) {
            return array_filter(self::$executionHistory, function ($item) use ($hookPoint) {
                return $item['hook_point'] === $hookPoint;
            });
        }

        return self::$executionHistory;
    }

    /**
     * Clear hook execution history
     */
    public static function clearHistory(): void
    {
        self::$executionHistory = [];
    }

    /**
     * Remove a hook
     */
    public static function removeHook(string $hookPoint, int $priority = null): void
    {
        if ($priority !== null) {
            unset(self::$hooks[$hookPoint][$priority]);
        } else {
            unset(self::$hooks[$hookPoint]);
        }
    }

    /**
     * Check if hook point exists
     */
    public static function hasHook(string $hookPoint): bool
    {
        return isset(self::$hooks[$hookPoint]) && !empty(self::$hooks[$hookPoint]);
    }

    /**
     * Count hooks for a specific point
     */
    public static function countHooks(string $hookPoint): int
    {
        if (!isset(self::$hooks[$hookPoint])) {
            return 0;
        }

        $count = 0;
        foreach (self::$hooks[$hookPoint] as $hooks) {
            $count += count($hooks);
        }

        return $count;
    }

    /**
     * Register common WHMCS hook points
     */
    public static function registerCommonHooks(): void
    {
        // These are placeholders - actual hooks are registered by modules
        $commonHookPoints = [
            // Client hooks
            'ClientAdd',
            'ClientEdit',
            'ClientDelete',
            'ClientLogin',
            'ClientLogout',
            'ClientChangePassword',

            // Order hooks
            'OrderPaid',
            'OrderCancelled',
            'OrderRefunded',
            'OrderStatusChange',

            // Invoice hooks
            'InvoiceCreated',
            'InvoicePaid',
            'InvoiceCancelled',
            'InvoicePaymentReminder',

            // Service hooks
            'ServiceEdit',
            'ServiceDelete',
            'ServiceRecurringCompleted',

            // Ticket hooks
            'TicketOpen',
            'TicketUserReply',
            'TicketAdminReply',
            'TicketClose',

            // Domain hooks
            'DomainRegister',
            'DomainTransfer',
            'DomainRenew',
            'DomainDelete',

            // Product hooks
            'AfterModuleCreate',
            'AfterModuleSuspend',
            'AfterModuleUnsuspend',
            'AfterModuleTerminate',
            'AfterModuleChangePassword',
            'AfterModuleChangePackage',

            // Daily cron
            'AfterCronJob',
            'DailyCronJob',
        ];

        foreach ($commonHookPoints as $hookPoint) {
            if (!isset(self::$hooks[$hookPoint])) {
                self::$hooks[$hookPoint] = [];
            }
        }
    }
}

/**
 * Global helper function for adding hooks (WHMCS compatibility)
 */
if (!function_exists('add_hook')) {
    function add_hook(string $hookPoint, int $priority, $function): void
    {
        \App\Numz\WHMCS\HookManager::add_hook($hookPoint, $priority, $function);
    }
}

/**
 * Global helper function for running hooks
 */
if (!function_exists('run_hook')) {
    function run_hook(string $hookPoint, array $params = []): array
    {
        return \App\Numz\WHMCS\HookManager::run($hookPoint, $params);
    }
}
