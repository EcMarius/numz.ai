<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StripeService;

class StripeConfigServiceProvider extends ServiceProvider
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
        // Override Wave's Stripe config with database settings if available
        try {
            $stripeService = app(StripeService::class);

            if ($stripeService->isConfigured()) {
                // Override config values with database settings
                config([
                    'wave.stripe.secret_key' => $stripeService->getSecretKey(),
                    'wave.stripe.publishable_key' => $stripeService->getPublishableKey(),
                    'wave.stripe.webhook_secret' => $stripeService->getWebhookSecret(),
                ]);

                // Also set for DevDojo billing if it exists
                if (config('devdojo.billing.keys')) {
                    config([
                        'devdojo.billing.keys.stripe.secret_key' => $stripeService->getSecretKey(),
                        'devdojo.billing.keys.stripe.publishable_key' => $stripeService->getPublishableKey(),
                        'devdojo.billing.keys.stripe.webhook_secret' => $stripeService->getWebhookSecret(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if settings table doesn't exist yet (during installation)
            \Log::debug('Stripe config override skipped: ' . $e->getMessage());
        }
    }
}
