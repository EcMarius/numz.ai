<?php

namespace Wave\Plugins;

use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Wave\Plugins\Contracts\PlatformFieldProvider;
use Wave\Plugins\Contracts\CampaignFieldProvider;

class PluginManager
{
    protected $app;

    protected $plugins = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        PluginAutoloader::register();
    }

    public function loadPlugins()
    {
        $installedPlugins = $this->getInstalledPlugins();

        foreach ($installedPlugins as $pluginName) {
            $studlyPluginName = Str::studly($pluginName);
            $pluginClass = "Wave\\Plugins\\{$studlyPluginName}\\{$studlyPluginName}Plugin";

            $expectedPath = $this->findPluginFile($pluginName);
            if ($expectedPath) {
                include_once $expectedPath;

                if (class_exists($pluginClass)) {
                    $plugin = new $pluginClass($this->app);
                    $this->plugins[$pluginName] = $plugin;
                    $this->app->register($plugin);

                    // Register Livewire components
                    $this->registerLivewireComponents($plugin);

                    // Register Filament resources and pages
                    $this->registerFilamentResources($plugin);
                    $this->registerFilamentPages($plugin);
                } else {
                    Log::warning("Plugin class not found after including file: {$pluginClass}");
                }
            } else {
                Log::warning("Plugin file not found for: {$pluginName}");
            }
        }
    }

    protected function registerLivewireComponents(Plugin $plugin)
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return;
        }

        $components = $plugin->getLivewireComponents();
        foreach ($components as $name => $component) {
            \Livewire\Livewire::component($name, $component);
        }
    }

    protected function registerFilamentResources(Plugin $plugin)
    {
        $resources = $plugin->getFilamentResources();
        foreach ($resources as $resource) {
            if (class_exists($resource)) {
                // Register with Filament panel
                // This will be handled by Filament's discovery
            }
        }
    }

    protected function registerFilamentPages(Plugin $plugin)
    {
        $pages = $plugin->getFilamentPages();
        foreach ($pages as $page) {
            if (class_exists($page)) {
                // Register with Filament panel
                // This will be handled by Filament's discovery
            }
        }
    }

    /**
     * Get a specific plugin instance
     */
    public function getPlugin(string $pluginName): ?Plugin
    {
        return $this->plugins[$pluginName] ?? null;
    }

    /**
     * Get all loaded plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get plugins that provide authentication methods
     */
    public function getAuthPlugins(): array
    {
        return array_filter($this->plugins, function (Plugin $plugin) {
            return $plugin->providesAuth();
        });
    }

    protected function findPluginFile($pluginName)
    {
        $basePath = resource_path('plugins');
        $studlyName = Str::studly($pluginName);

        // Check for exact case match
        $exactPath = "{$basePath}/{$studlyName}/{$studlyName}Plugin.php";
        if (File::exists($exactPath)) {
            return $exactPath;
        }

        // Check for case-insensitive match
        $directories = File::directories($basePath);
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            // Skip hidden directories and system files
            if (str_starts_with($dirName, '.')) {
                continue;
            }

            if (strtolower($dirName) === strtolower($pluginName)) {
                $filePath = "{$directory}/{$studlyName}Plugin.php";
                if (File::exists($filePath)) {
                    return $filePath;
                }
            }
        }

        return null;
    }

    protected function runPostActivationCommands(Plugin $plugin)
    {
        $commands = $plugin->getPostActivationCommands();

        foreach ($commands as $command) {
            if (is_string($command)) {
                Artisan::call($command);
            } elseif (is_callable($command)) {
                $command();
            }
        }
    }

    protected function getInstalledPlugins()
    {
        // Check if cache is available (not during package discovery)
        if ($this->app->bound('cache')) {
            try {
                return Cache::remember('wave_installed_plugins', 3600, function () {
                    $path = resource_path('plugins/installed.json');
                    if (! File::exists($path)) {
                        return [];
                    }

                    return File::json($path);
                });
            } catch (Exception $e) {
                // Fallback to direct file access if cache fails
            }
        }

        // Direct file access when cache is not available
        $path = resource_path('plugins/installed.json');
        if (! File::exists($path)) {
            return [];
        }

        return File::json($path);
    }

    /**
     * Get platform fields from all plugins for a specific platform.
     *
     * @param string $platformName
     * @return array Array of fields grouped by plugin
     */
    public function getPlatformFieldsForPlatform(string $platformName): array
    {
        $allFields = [];

        foreach ($this->plugins as $pluginName => $plugin) {
            if ($plugin instanceof PlatformFieldProvider) {
                $fields = $plugin->getPlatformFields($platformName);

                if (!empty($fields)) {
                    $pluginIdentifier = $plugin->getPluginIdentifier();
                    $allFields[$pluginIdentifier] = $fields;
                }
            }
        }

        return $allFields;
    }

    /**
     * Get all plugins that provide platform fields.
     *
     * @return array
     */
    public function getPlatformFieldProviders(): array
    {
        return array_filter($this->plugins, function (Plugin $plugin) {
            return $plugin instanceof PlatformFieldProvider;
        });
    }

    /**
     * Get campaign fields from all plugins for a specific platform.
     *
     * @param string $platformName
     * @param array $platformConfig Platform configuration from database
     * @return array Array of fields grouped by plugin
     */
    public function getCampaignFieldsForPlatform(string $platformName, array $platformConfig): array
    {
        $allFields = [];

        foreach ($this->plugins as $pluginName => $plugin) {
            if ($plugin instanceof CampaignFieldProvider) {
                $fields = $plugin->getCampaignFields($platformName, $platformConfig);

                if (!empty($fields)) {
                    $pluginIdentifier = $plugin->getPluginIdentifier();
                    $allFields[$pluginIdentifier] = $fields;
                }
            }
        }

        return $allFields;
    }

    /**
     * Get all plugins that provide campaign fields.
     *
     * @return array
     */
    public function getCampaignFieldProviders(): array
    {
        return array_filter($this->plugins, function (Plugin $plugin) {
            return $plugin instanceof CampaignFieldProvider;
        });
    }
}
