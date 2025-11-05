<?php

namespace Wave\Plugins;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

abstract class Plugin extends ServiceProvider
{
    protected $name;
    protected $version = '1.0.0';
    protected $description = '';
    protected $author = '';

    public function getName()
    {
        return $this->name;
    }

    // Provide default implementations
    public function register(): void
    {
        // Default register logic, if any
        // Can be overridden by specific plugins
    }

    public function boot(): void
    {
        // Default boot logic, if any
        // Can be overridden by specific plugins
    }

    // You can add additional methods that plugins should implement
    abstract public function getPluginInfo(): array;

    public function postActivation()
    {
        // Default implementation (empty)
        // Override this to run commands after activation
    }

    public function preDeactivation()
    {
        // Default implementation (empty)
        // Override this to run commands before deactivation
    }

    /**
     * Get sidebar menu items to add to the navigation
     *
     * @return array Example:
     * [
     *     [
     *         'label' => 'Dashboard',
     *         'url' => '/dashboard',
     *         'icon' => 'phosphor-house-duotone',
     *         'badge' => fn() => 5, // Optional, can be a closure or value
     *         'active' => fn() => request()->is('dashboard*'), // Optional active check
     *     ],
     *     [
     *         'label' => 'Settings',
     *         'icon' => 'phosphor-gear-duotone',
     *         'items' => [ // Sub-items
     *             ['label' => 'General', 'url' => '/settings/general'],
     *             ['label' => 'API', 'url' => '/settings/api'],
     *         ],
     *     ],
     * ]
     */
    public function getSidebarMenuItems(): array
    {
        return [];
    }

    /**
     * Get Filament navigation items to add to admin panel
     *
     * @return array Example:
     * [
     *     [
     *         'label' => 'My Resource',
     *         'url' => '/admin/my-resource',
     *         'icon' => 'heroicon-o-document',
     *         'sort' => 10,
     *         'badge' => fn() => 5,
     *         'group' => 'My Group', // Optional navigation group
     *     ],
     * ]
     */
    public function getFilamentNavigationItems(): array
    {
        return [];
    }

    /**
     * Get routes to override existing pages
     * Useful for modifying dashboard or other core pages
     *
     * @return array Example:
     * [
     *     'dashboard' => \MyPlugin\Pages\CustomDashboard::class,
     *     'settings' => \MyPlugin\Pages\CustomSettings::class,
     * ]
     */
    public function getPageOverrides(): array
    {
        return [];
    }

    /**
     * Get Livewire components to register
     *
     * @return array Example:
     * [
     *     'my-component' => \MyPlugin\Livewire\MyComponent::class,
     * ]
     */
    public function getLivewireComponents(): array
    {
        return [];
    }

    /**
     * Get Filament resources to register
     *
     * @return array Example:
     * [
     *     \MyPlugin\Filament\Resources\MyResource::class,
     * ]
     */
    public function getFilamentResources(): array
    {
        return [];
    }

    /**
     * Get Filament pages to register
     *
     * @return array Example:
     * [
     *     \MyPlugin\Filament\Pages\MyPage::class,
     * ]
     */
    public function getFilamentPages(): array
    {
        return [];
    }

    /**
     * Get migrations path for this plugin
     */
    public function getMigrationsPath(): ?string
    {
        return null;
    }

    /**
     * Get plugin settings schema for admin panel
     * This will automatically create a settings page in admin
     *
     * @return array Example:
     * [
     *     'api_key' => [
     *         'type' => 'text',
     *         'label' => 'API Key',
     *         'required' => true,
     *         'encrypted' => true,
     *     ],
     *     'enabled' => [
     *         'type' => 'toggle',
     *         'label' => 'Enable Feature',
     *         'default' => false,
     *     ],
     * ]
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * Get a plugin setting value
     */
    public function getSetting(string $key, $default = null)
    {
        $pluginSlug = \Illuminate\Support\Str::slug($this->name);
        $settingKey = "plugin.{$pluginSlug}.{$key}";

        return config($settingKey, $default);
    }

    /**
     * Set a plugin setting value
     */
    public function setSetting(string $key, $value): void
    {
        $pluginSlug = \Illuminate\Support\Str::slug($this->name);
        $settingKey = "plugin.{$pluginSlug}.{$key}";

        config([$settingKey => $value]);

        // Save to database if settings table exists
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            \Wave\Setting::set($settingKey, $value);
        }
    }

    /**
     * Communicate with another plugin
     *
     * @param string $pluginName
     * @return Plugin|null
     */
    public function getPlugin(string $pluginName): ?Plugin
    {
        return app()->make(\Wave\Plugins\PluginManager::class)->getPlugin($pluginName);
    }

    /**
     * Fire an event that other plugins can listen to
     */
    public function firePluginEvent(string $event, array $data = []): void
    {
        event("plugin.{$this->name}.{$event}", $data);
    }

    /**
     * Get the plugin's base path
     */
    public function getPluginPath(): string
    {
        $reflection = new \ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    /**
     * Get the plugin version
     */
    public function getPluginVersion(): array
    {
        $versionFile = $this->getPluginPath() . '/version.json';

        if (File::exists($versionFile)) {
            return File::json($versionFile);
        }

        return [
            'version' => $this->version,
        ];
    }

    /**
     * Check if this plugin provides authentication methods
     * Override this method and return true in plugins that provide auth
     */
    public function providesAuth(): bool
    {
        return false;
    }

    /**
     * Render authentication buttons for login/register pages
     * Override this method in plugins that provide authentication
     *
     * @param string $page 'login' or 'register'
     * @return string HTML to render
     */
    public function renderAuthButtons(string $page): string
    {
        return '';
    }
}
