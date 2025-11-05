<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAndOptimize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-and-optimize {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches and optimize the application for production';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Clearing all caches...');
        $this->newLine();

        // Clear configuration cache
        $this->call('config:clear');
        $this->info('✓ Configuration cache cleared');

        // Clear application cache
        $this->call('cache:clear');
        $this->info('✓ Application cache cleared');

        // Clear route cache
        $this->call('route:clear');
        $this->info('✓ Route cache cleared');

        // Clear view cache
        $this->call('view:clear');
        $this->info('✓ View cache cleared');

        // Clear compiled files
        $this->call('clear-compiled');
        $this->info('✓ Compiled files cleared');

        $this->newLine();
        $this->info('⚡ Optimizing application...');
        $this->newLine();

        // Optimize the application (caches config, routes, views, events)
        $this->call('optimize');
        $this->info('✓ Application optimized');

        // Cache Filament components if available
        if (class_exists(\Filament\Facades\Filament::class)) {
            try {
                $this->call('filament:optimize');
                $this->info('✓ Filament optimized');
            } catch (\Exception $e) {
                // Silently skip if Filament optimization fails
            }
        }

        $this->newLine();
        $this->info('✅ All done! Application is now optimized for fast performance.');

        return self::SUCCESS;
    }
}
