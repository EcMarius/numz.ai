<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Numz\WHMCS\ModuleLoader;
use App\Numz\WHMCS\HookManager;
use App\Numz\WHMCS\API;

/**
 * WHMCS Compatibility Service Provider
 *
 * Initializes and registers all WHMCS compatibility components:
 * - Module loader and discovery
 * - Hook system and registration
 * - API routes and middleware
 * - Global helper functions
 * - Database compatibility
 */
class WHMCSCompatibilityServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge WHMCS configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/whmcs.php',
            'whmcs'
        );

        // Register WHMCS components as singletons
        $this->app->singleton('whmcs.modules', function ($app) {
            return new ModuleLoader();
        });

        $this->app->singleton('whmcs.hooks', function ($app) {
            return new HookManager();
        });

        $this->app->singleton('whmcs.api', function ($app) {
            return new API();
        });

        // Register global helper functions if not already defined
        $this->registerHelperFunctions();

        // Register class aliases for WHMCS compatibility
        $this->registerClassAliases();
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Only boot if WHMCS compatibility is enabled
        if (!config('whmcs.enabled', false)) {
            return;
        }

        // Initialize module loader
        $this->initializeModuleLoader();

        // Register hook system
        $this->initializeHookSystem();

        // Register API routes
        $this->registerAPIRoutes();

        // Load WHMCS hooks from hook directories
        $this->loadWHMCSHooks();

        // Publish configuration if needed
        $this->publishes([
            __DIR__ . '/../../config/whmcs.php' => config_path('whmcs.php'),
        ], 'whmcs-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/2024_01_15_000001_create_whmcs_compatibility_tables.php'
                => database_path('migrations/2024_01_15_000001_create_whmcs_compatibility_tables.php'),
        ], 'whmcs-migrations');
    }

    /**
     * Initialize the module loader
     */
    protected function initializeModuleLoader(): void
    {
        ModuleLoader::init();

        // Discover all available modules on boot
        if (config('whmcs.auto_discover_modules', true)) {
            try {
                $discovered = ModuleLoader::discoverModules();

                if (!empty($discovered)) {
                    \Log::info('WHMCS modules discovered', [
                        'count' => count($discovered, COUNT_RECURSIVE) - count($discovered),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('WHMCS module discovery failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Initialize the hook system
     */
    protected function initializeHookSystem(): void
    {
        // Register common WHMCS hook points
        HookManager::registerCommonHooks();

        // Register Laravel event listeners that trigger WHMCS hooks
        $this->registerEventToHookBridge();
    }

    /**
     * Register API routes for WHMCS compatibility
     */
    protected function registerAPIRoutes(): void
    {
        if (!config('whmcs.api.enabled', false)) {
            return;
        }

        Route::group([
            'prefix' => 'includes/api.php',
            'middleware' => ['api', 'whmcs.api'],
        ], function () {
            Route::post('/', [API::class, 'handle']);
        });

        // Alternative modern route
        Route::group([
            'prefix' => 'api/whmcs',
            'middleware' => ['api', 'whmcs.api'],
        ], function () {
            Route::post('/', [API::class, 'handle']);
            Route::post('/{command}', [API::class, 'handle']);
        });
    }

    /**
     * Load WHMCS hooks from hook directories
     */
    protected function loadWHMCSHooks(): void
    {
        $hookPaths = config('whmcs.hook_paths', []);

        foreach ($hookPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            // Load all PHP files in hook directory
            $hookFiles = glob($path . '/*.php');

            foreach ($hookFiles as $hookFile) {
                try {
                    require_once $hookFile;

                    \Log::debug('WHMCS hook loaded', [
                        'file' => basename($hookFile),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('WHMCS hook loading failed', [
                        'file' => basename($hookFile),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Register global helper functions
     */
    protected function registerHelperFunctions(): void
    {
        // Hook management functions
        if (!function_exists('add_hook')) {
            function add_hook(string $hookPoint, int $priority, $function): void {
                \App\Numz\WHMCS\HookManager::add_hook($hookPoint, $priority, $function);
            }
        }

        if (!function_exists('run_hook')) {
            function run_hook(string $hookPoint, array $params = []): array {
                return \App\Numz\WHMCS\HookManager::run($hookPoint, $params);
            }
        }

        // API functions
        if (!function_exists('localAPI')) {
            function localAPI(string $command, array $params = [], string $adminUser = null): array {
                return \App\Numz\WHMCS\API::execute($command, $params, $adminUser);
            }
        }

        // Module functions
        if (!function_exists('whmcs_call_module')) {
            function whmcs_call_module(string $type, string $module, string $function, array $params = []): mixed {
                return \App\Numz\WHMCS\ModuleLoader::callModuleFunction($type, $module, $function, $params);
            }
        }

        // Legacy compatibility functions
        if (!function_exists('getCustomFields')) {
            function getCustomFields(string $type, int $relId): array {
                return \DB::table('tblcustomfields')
                    ->where('type', $type)
                    ->where('relid', $relId)
                    ->get()
                    ->toArray();
            }
        }

        if (!function_exists('getClientsDetails')) {
            function getClientsDetails(int $clientId): ?object {
                return \DB::table('tblclients')
                    ->where('id', $clientId)
                    ->first();
            }
        }

        if (!function_exists('logModuleCall')) {
            function logModuleCall(
                string $module,
                string $action,
                $request,
                $response,
                $processedData = '',
                array $replaceVars = []
            ): int {
                $logId = \DB::table('tblmodulelog')->insertGetId([
                    'module' => $module,
                    'action' => $action,
                    'request' => is_array($request) ? json_encode($request) : $request,
                    'response' => is_array($response) ? json_encode($response) : $response,
                    'arrdata' => is_array($processedData) ? serialize($processedData) : $processedData,
                    'created_at' => now(),
                ]);

                return $logId;
            }
        }

        if (!function_exists('logActivity')) {
            function logActivity(string $description, int $userId = 0): void {
                \DB::table('tblactivitylog')->insert([
                    'date' => now(),
                    'description' => $description,
                    'user' => auth()->user()->email ?? 'System',
                    'userid' => $userId ?: (auth()->id() ?? 0),
                    'ipaddr' => request()->ip(),
                ]);
            }
        }
    }

    /**
     * Register class aliases for WHMCS compatibility
     */
    protected function registerClassAliases(): void
    {
        // Capsule ORM alias
        if (!class_exists('Capsule')) {
            class_alias(\App\Numz\WHMCS\Capsule::class, 'Capsule');
        }

        // Additional WHMCS class aliases can be added here
    }

    /**
     * Bridge Laravel events to WHMCS hooks
     */
    protected function registerEventToHookBridge(): void
    {
        // Map Laravel events to WHMCS hook points

        // User events
        \Event::listen(\Illuminate\Auth\Events\Registered::class, function ($event) {
            run_hook('ClientAdd', ['userid' => $event->user->id]);
        });

        \Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            run_hook('ClientLogin', ['userid' => $event->user->id]);
        });

        // Invoice events
        \Event::listen(\App\Events\InvoiceCreated::class, function ($event) {
            run_hook('InvoiceCreated', ['invoiceid' => $event->invoice->id]);
        });

        \Event::listen(\App\Events\InvoicePaid::class, function ($event) {
            run_hook('InvoicePaid', ['invoiceid' => $event->invoice->id]);
        });

        // Order events
        \Event::listen(\App\Events\OrderPaid::class, function ($event) {
            run_hook('OrderPaid', ['orderid' => $event->order->id]);
        });

        // Service events
        \Event::listen(\App\Events\ServiceCreated::class, function ($event) {
            run_hook('AfterModuleCreate', ['serviceid' => $event->service->id]);
        });

        \Event::listen(\App\Events\ServiceSuspended::class, function ($event) {
            run_hook('AfterModuleSuspend', ['serviceid' => $event->service->id]);
        });

        \Event::listen(\App\Events\ServiceUnsuspended::class, function ($event) {
            run_hook('AfterModuleUnsuspend', ['serviceid' => $event->service->id]);
        });

        \Event::listen(\App\Events\ServiceTerminated::class, function ($event) {
            run_hook('AfterModuleTerminate', ['serviceid' => $event->service->id]);
        });

        // Ticket events
        \Event::listen(\App\Events\TicketOpened::class, function ($event) {
            run_hook('TicketOpen', ['ticketid' => $event->ticket->id]);
        });

        \Event::listen(\App\Events\TicketReply::class, function ($event) {
            run_hook('TicketUserReply', [
                'ticketid' => $event->ticket->id,
                'replyid' => $event->reply->id,
            ]);
        });

        // Domain events
        \Event::listen(\App\Events\DomainRegistered::class, function ($event) {
            run_hook('DomainRegister', ['domainid' => $event->domain->id]);
        });

        \Event::listen(\App\Events\DomainTransferred::class, function ($event) {
            run_hook('DomainTransfer', ['domainid' => $event->domain->id]);
        });
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            'whmcs.modules',
            'whmcs.hooks',
            'whmcs.api',
        ];
    }
}
