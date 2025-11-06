<?php

namespace App\Providers;

use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only load debugbar in local/staging environments, not in production
        // This prevents missing config file errors on production server
        if ($this->app->environment(['local', 'staging'])) {
            if (class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
                $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
            }
        }

        // Register NgrokService as singleton
        $this->app->singleton(\App\Services\NgrokService::class);

        // Register StripeService as singleton
        $this->app->singleton(\App\Services\StripeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment() == 'production') {
            $this->app['request']->server->set('HTTPS', true);

            // Force HTTPS for all URL generation (fixes Livewire signed URL validation)
            URL::forceScheme('https');
        }

        $this->setSchemaDefaultLength();

        Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
            $explode = explode(',', $value);
            $allow = ['png', 'jpg', 'svg', 'jpeg'];
            $format = str_replace(
                [
                    'data:image/',
                    ';',
                    'base64',
                ],
                [
                    '', '', '',
                ],
                $explode[0]
            );

            // check file format
            if (! in_array($format, $allow)) {
                return false;
            }

            // check base64 format
            if (! preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                return false;
            }

            return true;
        });

        // Register growth hacking trial activation listener
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\ActivateTrialOnLogin::class
        );

        // Register ONLY our custom verification email listener
        // This prevents Laravel's default SendEmailVerificationNotification from running
        Event::forget(\Illuminate\Auth\Events\Registered::class);
        Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            \App\Listeners\UserRegistered::class
        );

        // Register Setting observer to clear cache on updates (ensures instant favicon/logo changes)
        \Wave\Setting::observe(\App\Observers\SettingObserver::class);

        // Register billing automation event listeners
        Event::listen(
            \App\Events\PaymentCompleted::class,
            function (\App\Events\PaymentCompleted $event) {
                // Mark invoice as paid if payment is for an invoice
                if ($event->payment->invoice_id) {
                    $invoice = \App\Models\Invoice::find($event->payment->invoice_id);
                    if ($invoice && $invoice->status === 'unpaid') {
                        $invoice->markAsPaid($event->payment->gateway, $event->payment->transaction_id);
                        event(new \App\Events\InvoicePaid($invoice));
                    }
                }
            }
        );

        Event::listen(
            \App\Events\InvoicePaid::class,
            \App\Listeners\ProcessInvoicePayment::class
        );

        Event::listen(
            \App\Events\ServiceCreated::class,
            \App\Listeners\ProvisionServiceAutomatically::class
        );

        // Override Livewire's file upload route to bypass broken signature validation
        Route::post('/livewire/upload-file', [\App\Http\Controllers\Admin\FileUploadController::class, 'handle'])
            ->middleware(['web'])
            ->name('livewire.upload-file');

        // Override DevDojo Auth's email verification route to use hash-based verification
        // This MUST be registered here (after DevDojo Auth loads) to override their signed URL route
        Route::get('/auth/verify-email/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
            ->middleware(['web'])
            ->name('verification.verify');

        $this->bootRoute();
    }

    private function setSchemaDefaultLength(): void
    {
        try {
            Schema::defaultStringLength(191);
        } catch (Exception $exception) {
        }
    }

    public function bootRoute()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

    }
}
