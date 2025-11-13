<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallSecurityFeatures extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:install
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install security features (migrations, seeders, configs)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Security Features...');
        $this->newLine();

        // Run migrations
        $this->info('Running migrations...');
        $this->call('migrate');
        $this->newLine();

        // Seed roles and permissions
        $this->info('Seeding roles and permissions...');
        $this->call('db:seed', [
            '--class' => 'RolesAndPermissionsSeeder',
        ]);
        $this->newLine();

        // Install composer packages (if needed)
        $this->info('Checking composer packages...');
        $this->checkComposerPackages();
        $this->newLine();

        // Create storage directories
        $this->info('Creating storage directories...');
        $this->createDirectories();
        $this->newLine();

        $this->info('Security features installed successfully!');
        $this->newLine();

        $this->displayNextSteps();

        return Command::SUCCESS;
    }

    /**
     * Check required composer packages
     */
    protected function checkComposerPackages(): void
    {
        $packages = [
            'spatie/laravel-permission' => 'Role and Permission management',
            'pragmarx/google2fa' => 'Two-Factor Authentication',
            'bacon/bacon-qr-code' => 'QR Code generation',
        ];

        foreach ($packages as $package => $description) {
            if ($this->isPackageInstalled($package)) {
                $this->info("✓ {$package} - {$description}");
            } else {
                $this->warn("✗ {$package} - {$description} (NOT INSTALLED)");
                $this->line("  Run: composer require {$package}");
            }
        }
    }

    /**
     * Check if composer package is installed
     */
    protected function isPackageInstalled(string $package): bool
    {
        $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

        if (!$composerLock) {
            return false;
        }

        foreach ($composerLock['packages'] as $installedPackage) {
            if ($installedPackage['name'] === $package) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create necessary directories
     */
    protected function createDirectories(): void
    {
        $directories = [
            storage_path('app/exports'),
            storage_path('app/gdpr'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
                $this->info("Created: {$directory}");
            } else {
                $this->line("Exists: {$directory}");
            }
        }
    }

    /**
     * Display next steps
     */
    protected function displayNextSteps(): void
    {
        $this->info('Next Steps:');
        $this->newLine();

        $steps = [
            '1. Update your .env file with security configurations (see .env.example.security)',
            '2. Add security routes to your routes/web.php (see routes/security.example.php)',
            '3. Register middleware in app/Http/Kernel.php:',
            '   - IpWhitelistMiddleware',
            '   - BruteForceProtection',
            '   - SecurityHeadersMiddleware',
            '   - SessionTimeout',
            '   - SecurePasswordMiddleware',
            '   - RolePermissionMiddleware',
            '4. Configure services in config/services.php:',
            '   - reCAPTCHA',
            '   - Twilio (for SMS 2FA)',
            '5. Publish Spatie Permission config:',
            '   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"',
            '6. Add the Auditable trait to models you want to audit',
            '7. Test the security features in your development environment',
        ];

        foreach ($steps as $step) {
            $this->line($step);
        }

        $this->newLine();
        $this->warn('Important: Review all security settings before deploying to production!');
    }
}
