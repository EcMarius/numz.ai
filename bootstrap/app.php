<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Lab404\Impersonate\ImpersonateServiceProvider::class,
        \Wave\WaveServiceProvider::class,
        \DevDojo\Themes\ThemesServiceProvider::class,
        \DevDojo\Themes\ThemesServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();

            // If user is not verified, redirect to verification notice
            if ($user && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
                return route('verification.notice');
            }

            // Otherwise redirect to home
            return AppServiceProvider::HOME;
        });

        // Trust all proxies for production (required for signed URLs to work behind nginx/apache)
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->encryptCookies(except: [
            'XSRF-TOKEN', // Livewire needs to read this cookie
        ]);

        $middleware->validateCsrfTokens(except: [
            '/webhook/paddle',
            '/webhook/stripe',
            '/stripe/webhook',
        ]);

        $middleware->append(\Filament\Http\Middleware\DisableBladeIconComponents::class);

        $middleware->web(\RalphJSmit\Livewire\Urls\Middleware\LivewireUrlsMiddleware::class);

        // Check installation status (redirects to installer if not installed)
        $middleware->web(\App\Http\Middleware\CheckInstallation::class);

        // Auto-start ngrok on localhost (runs once per hour via cache)
        $middleware->web(\App\Http\Middleware\NgrokAutoStart::class);

        // Auto-start queue worker if not running (checks every 2 minutes via cache)
        $middleware->web(\App\Http\Middleware\EnsureQueueWorkerRunning::class);

        // Coming soon mode - show coming-soon.html to non-logged-in users when enabled
        $middleware->web(\App\Http\Middleware\ComingSoon::class);

        // Ensure users complete onboarding before accessing protected areas (BEFORE plan check!)
        $middleware->web(\App\Http\Middleware\EnsureOnboardingCompleted::class);

        // Apply plan check to authenticated web routes
        $middleware->web(\App\Http\Middleware\PlanCheck::class);

        // Organization setup handled via subscription/welcome page redirect only
        // Middleware disabled to prevent redirect loops
        // $middleware->web(\App\Http\Middleware\EnsureOrganizationSetup::class);

        $middleware->throttleApi();

        // Enable CORS for API routes (required for browser extension to work from any domain)
        $middleware->api([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'plan.check' => \App\Http\Middleware\PlanCheck::class,
            'api.key' => \App\Http\Middleware\AuthenticateApiKey::class,
            'api.logger' => \App\Http\Middleware\ApiLogger::class,
            'api.version' => \App\Http\Middleware\ApiVersionCheck::class,
            'enforce.limits' => \App\Http\Middleware\EnforcePlanLimits::class,
            'organization.setup' => \App\Http\Middleware\EnsureOrganizationSetup::class,
            'coming.soon' => \App\Http\Middleware\ComingSoon::class,
            'onboarding.completed' => \App\Http\Middleware\EnsureOnboardingCompleted::class,
            'whmcs.api' => \App\Http\Middleware\WHMCSApiMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
