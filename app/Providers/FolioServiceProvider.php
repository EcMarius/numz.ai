<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class FolioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom auth pages to override DevDojo Auth vendor pages
        // This must be registered early to take priority over vendor/devdojo/auth
        $customAuthPagesPath = resource_path('views/pages/auth');
        if (file_exists($customAuthPagesPath)) {
            Folio::path($customAuthPagesPath)->middleware([
                '*' => ['guest'],
            ]);
        }
    }
}
