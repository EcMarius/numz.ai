<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\DashboardWidget;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
// use Filament\Widgets;
// use BezhanSalleh\FilamentGoogleAnalytics\Widgets;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Wave\Widgets;

class AdminPanelProvider extends PanelProvider
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-presentation-chart-line';
    }

    public function panel(Panel $panel): Panel
    {

        Blade::component('wave::admin.components.label', 'label');

        // Get plugin pages and resources before building panel
        $pluginPages = $this->getPluginPages();
        $pluginResources = $this->getPluginResources();

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources(array_merge($pluginResources, [
                \App\Filament\Resources\Stats\StatResource::class,
                \App\Filament\Resources\Testimonials\TestimonialResource::class,
            ]))
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages($pluginPages)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // ->discoverWidgets(in: app_path('BezhanSalleh\FilamentGoogleAnalytics\Widgets'), for: 'BezhanSalleh\\FilamentGoogleAnalytics\\Widgets')
            ->widgets([
                DashboardWidget::class,
            ])
            ->plugin(\Wave\Plugins\EvenLeads\Filament\EvenLeadsFilamentPlugin::make())
            ->plugin(\Wave\Plugins\SocialAuth\Filament\SocialAuthFilamentPlugin::make())
            ->plugin(\Wave\Plugins\KPIs\Filament\KPIsFilamentPlugin::make())
            ->plugin(\Wave\Plugins\PostHog\Filament\PostHogFilamentPlugin::make())
            ->plugin(\Wave\Plugins\Apify\Filament\ApifyFilamentPlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // \App\Http\Middleware\WaveEditTab::class
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'dashboard' => MenuItem::make()
                    ->label('Go to Dashboard')
                    ->url(fn () => route('dashboard'))
                    ->icon('heroicon-o-home'),
            ])
            ->brandLogo(fn () => view('wave::admin.logo'))
            ->darkModeBrandLogo(fn () => view('wave::admin.logo-dark'));
    }

    protected function getPluginPages(): array
    {
        if (!app()->bound(\Wave\Plugins\PluginManager::class)) {
            return [];
        }

        $pluginManager = app(\Wave\Plugins\PluginManager::class);
        $plugins = $pluginManager->getPlugins();

        $pages = [];
        foreach ($plugins as $plugin) {
            $pluginPages = $plugin->getFilamentPages();
            if (!empty($pluginPages)) {
                $pages = array_merge($pages, $pluginPages);
            }
        }

        return $pages;
    }

    protected function getPluginResources(): array
    {
        if (!app()->bound(\Wave\Plugins\PluginManager::class)) {
            return [];
        }

        $pluginManager = app(\Wave\Plugins\PluginManager::class);
        $plugins = $pluginManager->getPlugins();

        $resources = [];
        foreach ($plugins as $plugin) {
            $pluginResources = $plugin->getFilamentResources();
            if (!empty($pluginResources)) {
                $resources = array_merge($resources, $pluginResources);
            }
        }

        return $resources;
    }
}
