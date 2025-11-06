<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Wave\Facades\Wave;
use App\Http\Controllers\PluginUploadController;
use App\Http\Controllers\BlogAIController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Client\ProductCatalogController;
use App\Http\Controllers\Client\CartController;
use App\Http\Controllers\Client\CheckoutController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\DomainController;
use App\Http\Controllers\Client\ClientPortalController;
use App\Http\Controllers\Client\SupportTicketController;
use App\Http\Controllers\Client\KnowledgeBaseController;
use App\Http\Controllers\WebhookController;

// Client Area - Public Routes
Route::prefix('products')->name('client.products.')->group(function () {
    Route::get('/', [ProductCatalogController::class, 'index'])->name('index');
    Route::get('/{slug}', [ProductCatalogController::class, 'show'])->name('show');
});

// Knowledge Base - Public Routes
Route::prefix('knowledge-base')->name('client.kb.')->group(function () {
    Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
    Route::get('/category/{slug}', [KnowledgeBaseController::class, 'category'])->name('category');
    Route::get('/{categorySlug}/{articleSlug}', [KnowledgeBaseController::class, 'article'])->name('article');
    Route::post('/article/{articleId}/vote', [KnowledgeBaseController::class, 'vote'])->name('article.vote');
    Route::post('/article/{articleId}/comment', [KnowledgeBaseController::class, 'comment'])->name('article.comment')->middleware('auth');
    Route::get('/article/{articleId}/attachment/{attachmentId}', [KnowledgeBaseController::class, 'downloadAttachment'])->name('article.attachment');
});

Route::prefix('domains')->name('client.domains.')->group(function () {
    Route::get('/search', [DomainController::class, 'index'])->name('search');
    Route::post('/check', [DomainController::class, 'checkAvailability'])->name('check');
    Route::post('/bulk-search', [DomainController::class, 'bulkSearch'])->name('bulk-search');
});

Route::prefix('cart')->name('client.cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add-product/{slug}', [ProductCatalogController::class, 'addToCart'])->name('add-product');
    Route::post('/add-domain', [DomainController::class, 'addToCart'])->name('add-domain');
    Route::delete('/remove/{itemId}', [CartController::class, 'remove'])->name('remove');
    Route::put('/update/{itemId}', [CartController::class, 'update'])->name('update');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/count', [CartController::class, 'count'])->name('count');
});

// Client Area - Authenticated Routes
Route::middleware(['web', 'auth'])->group(function () {
    // Checkout
    Route::prefix('checkout')->name('client.checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
    });

    // Payment Processing
    Route::prefix('payment')->name('client.payment.')->group(function () {
        Route::get('/{gateway}/{invoice}', [PaymentController::class, 'show'])->name('show');
        Route::post('/stripe', [PaymentController::class, 'processStripe'])->name('stripe');
        Route::post('/paypal', [PaymentController::class, 'processPayPal'])->name('paypal');
        Route::get('/paypal/return/{invoice}', [PaymentController::class, 'paypalReturn'])->name('paypal.return');
        Route::post('/coinbase', [PaymentController::class, 'processCoinbase'])->name('coinbase');
        Route::get('/success/{invoice}', [PaymentController::class, 'success'])->name('success');
        Route::get('/failed', [PaymentController::class, 'failed'])->name('failed');
    });

    // Client Portal
    Route::prefix('portal')->name('client.')->group(function () {
        Route::get('/dashboard', [ClientPortalController::class, 'dashboard'])->name('dashboard');

        // Services
        Route::get('/services', [ClientPortalController::class, 'services'])->name('services');
        Route::get('/services/{id}', [ClientPortalController::class, 'showService'])->name('services.show');
        Route::post('/services/{id}/cancel', [ClientPortalController::class, 'cancelService'])->name('services.cancel');

        // Domains
        Route::get('/domains', [ClientPortalController::class, 'domains'])->name('domains.index');
        Route::get('/domains/{id}', [ClientPortalController::class, 'showDomain'])->name('domains.show');
        Route::put('/domains/{id}/nameservers', [ClientPortalController::class, 'updateNameservers'])->name('domains.nameservers');
        Route::post('/domains/{id}/toggle-autorenew', [ClientPortalController::class, 'toggleAutoRenew'])->name('domains.toggle-autorenew');

        // Invoices
        Route::get('/invoices', [ClientPortalController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{id}', [ClientPortalController::class, 'showInvoice'])->name('invoices.show');
        Route::get('/invoices/{id}/pdf', [ClientPortalController::class, 'viewInvoice'])->name('invoices.pdf');
        Route::get('/invoices/{id}/download', [ClientPortalController::class, 'downloadInvoice'])->name('invoices.download');
        Route::post('/invoices/{id}/pay', [ClientPortalController::class, 'payInvoice'])->name('invoices.pay');

        // Support Tickets
        Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{id}', [SupportTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{id}/reply', [SupportTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{id}/close', [SupportTicketController::class, 'close'])->name('tickets.close');
        Route::post('/tickets/{id}/reopen', [SupportTicketController::class, 'reopen'])->name('tickets.reopen');
        Route::get('/tickets/{ticketId}/attachments/{attachmentId}/download', [SupportTicketController::class, 'downloadAttachment'])->name('tickets.download-attachment');
    });
});

// Payment Gateway Webhooks (no auth required, verified by signature)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/stripe', [WebhookController::class, 'stripe'])->name('stripe');
    Route::post('/paypal', [WebhookController::class, 'paypal'])->name('paypal');
    Route::post('/coinbase', [WebhookController::class, 'coinbase'])->name('coinbase');
    Route::post('/razorpay', [WebhookController::class, 'razorpay'])->name('razorpay');
    Route::post('/2checkout', [WebhookController::class, 'twoCheckout'])->name('2checkout');
    Route::post('/paysafecard', [WebhookController::class, 'paysafecard'])->name('paysafecard');
});

// Installer Routes (must be before any middleware)
Route::prefix('install')->name('installer.')->group(function () {
    Route::get('/', [InstallerController::class, 'index'])->name('index');
    Route::get('/requirements', [InstallerController::class, 'requirements'])->name('requirements');
    Route::get('/license', [InstallerController::class, 'license'])->name('license');
    Route::post('/license/verify', [InstallerController::class, 'verifyLicense'])->name('license.verify');
    Route::get('/database', [InstallerController::class, 'database'])->name('database');
    Route::post('/database/test', [InstallerController::class, 'testDatabase'])->name('database.test');
    Route::get('/admin', [InstallerController::class, 'admin'])->name('admin');
    Route::post('/install', [InstallerController::class, 'install'])->name('install');
});

// Social Authentication Routes
Route::prefix('auth/social')->name('social.')->group(function () {
    Route::get('{provider}', [SocialAuthController::class, 'redirect'])->name('redirect');
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])->name('callback');
});

// Unlink social account (authenticated)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/settings/social/{provider}/unlink', [SocialAuthController::class, 'unlink'])->name('social.unlink');
});

// NOTE: Email verification route is registered in AppServiceProvider::boot()
// to ensure it overrides DevDojo Auth's route (which uses signed URLs)

// Plugin upload route (AJAX)
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/admin/plugins/upload', [PluginUploadController::class, 'upload'])->name('admin.plugins.upload');

    // API Keys list for documentation (authenticated users only)
    Route::get('/api/user/api-keys', [\App\Http\Controllers\Api\UserApiKeysController::class, 'index'])->name('user.api-keys');

    // Lead detail view
    Route::get('/dashboard/leads/{id}', function($id) {
        return view('theme::pages.dashboard.leads.view', ['id' => $id]);
    })->name('dashboard.leads.view');

    // Blog AI routes
    Route::prefix('admin/blog/ai')->name('admin.blog.ai.')->group(function () {
        Route::get('/models', [BlogAIController::class, 'getModels'])->name('models');
        Route::post('/generate', [BlogAIController::class, 'generateContent'])->name('generate');
        Route::post('/edit', [BlogAIController::class, 'editText'])->name('edit');
        Route::post('/shorter', [BlogAIController::class, 'makeShorter'])->name('shorter');
        Route::post('/longer', [BlogAIController::class, 'makeLonger'])->name('longer');
        Route::post('/seo', [BlogAIController::class, 'optimizeForSEO'])->name('seo');
        Route::post('/reword', [BlogAIController::class, 'reword'])->name('reword');
    });
});

// Override default DevDojo Auth social routes with custom controller
// DISABLED: Using SocialAuth plugin routes instead (/social/auth/{provider})
// Route::get('auth/{driver}/redirect', [\App\Http\Controllers\Auth\CustomSocialController::class, 'redirect'])->name('auth.redirect');
// Route::get('auth/{driver}/callback', [\App\Http\Controllers\Auth\CustomSocialController::class, 'callback'])->name('auth.callback');

// General OAuth 2.0 Routes (for Browser Extension, Mobile Apps, etc.)
Route::get('/oauth/authorize', [\App\Http\Controllers\OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::get('/oauth/consent', [\App\Http\Controllers\OAuthController::class, 'showConsent'])->name('oauth.consent')->middleware('auth');
Route::post('/oauth/consent', [\App\Http\Controllers\OAuthController::class, 'handleConsent'])->name('oauth.consent.handle')->middleware('auth');
Route::get('/oauth/callback', [\App\Http\Controllers\OAuthController::class, 'callback'])->name('oauth.callback');

// Organization routes (for seated plans)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/organization/setup', [\App\Http\Controllers\OrganizationController::class, 'setup'])->name('organization.setup');
    Route::post('/organization/store', [\App\Http\Controllers\OrganizationController::class, 'store'])->name('organization.store');
    Route::get('/team', [\App\Http\Controllers\OrganizationController::class, 'team'])->name('organization.team');
    Route::get('/team/add', [\App\Http\Controllers\OrganizationController::class, 'addMemberForm'])->name('organization.add-member');
    Route::post('/team/add', [\App\Http\Controllers\OrganizationController::class, 'storeMember'])->name('organization.store-member');
    Route::get('/team/{member}', [\App\Http\Controllers\OrganizationController::class, 'showMember'])->name('organization.show-member');
    Route::delete('/team/{member}', [\App\Http\Controllers\OrganizationController::class, 'destroyMember'])->name('organization.destroy-member');
});

// Plan-related routes (must be outside PlanCheck middleware)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/plan-selection', function () {
        return view('theme::pages.plan-selection');
    })->name('plan-selection');

    Route::get('/trial-ended', function () {
        return view('theme::pages.trial-ended');
    })->name('trial-ended');

    Route::get('/plan-expired', function () {
        return view('theme::pages.plan-expired');
    })->name('plan-expired');

    // Quick checkout route (redirects directly to Stripe)
    Route::get('/checkout/{plan}', function (\Wave\Plan $plan) {
        \Log::info('CHECKOUT ROUTE HIT', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'billing' => request()->get('billing'),
            'user_id' => auth()->user()?->id,
        ]);

        $billingCycle = request()->get('billing', 'monthly');
        $billingCycleNormalized = in_array($billingCycle, ['monthly', 'month']) ? 'month' : 'year';

        // Get seat quantity for seated plans (default to 1)
        $seats = request()->get('seats', 1);
        $seats = max(1, min(50, (int) $seats)); // Ensure seats is between 1 and 50

        // Use StripeService to get correct credentials (respects test/live mode from EvenLeads settings)
        $stripeService = app(\App\Services\StripeService::class);

        \Log::info('Stripe configured check', ['is_configured' => $stripeService->isConfigured()]);

        if (!$stripeService->isConfigured()) {
            \Log::error('CHECKOUT FAILED: Stripe not configured');
            return redirect()->route('plan-selection')->with('error', 'Stripe is not configured. Please contact support.');
        }

        $stripe = new \Stripe\StripeClient($stripeService->getSecretKey());
        $priceId = $billingCycleNormalized == 'month' ? $plan->monthly_price_id : $plan->yearly_price_id;

        \Log::info('Price ID check', [
            'billing_cycle' => $billingCycleNormalized,
            'monthly_price_id' => $plan->monthly_price_id,
            'yearly_price_id' => $plan->yearly_price_id,
            'selected_price_id' => $priceId,
        ]);

        if (!$priceId) {
            \Log::error('CHECKOUT FAILED: No price ID for plan', [
                'plan' => $plan->name,
                'billing' => $billingCycleNormalized,
            ]);
            return redirect()->route('plan-selection')->with('error', 'Invalid plan or billing cycle.');
        }

        $sessionData = [
            'line_items' => [[
                'price' => $priceId,
                'quantity' => $plan->is_seated_plan ? $seats : 1,
            ]],
            'metadata' => [
                'billable_type' => 'user',
                'billable_id' => auth()->user()->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycleNormalized,
                'seats' => $plan->is_seated_plan ? $seats : 1,
            ],
            'mode' => 'subscription',
            'success_url' => url('subscription/welcome'),
            'cancel_url' => url('plan-selection'),
        ];

        // Add trial period if user doesn't have a subscription and hasn't used trial
        $user = auth()->user();
        $trialDays = (int) config('wave.trial.days', 7);
        $defaultTrialPlanId = config('wave.trial.plan_id', null);

        \Log::info('Trial check', [
            'trial_days' => $trialDays,
            'default_trial_plan_id' => $defaultTrialPlanId,
            'current_plan_id' => $plan->id,
            'user_has_subscription' => !is_null($user->subscription),
            'user_trial_ends_at' => $user->trial_ends_at,
        ]);

        if ($trialDays > 0 && $defaultTrialPlanId == $plan->id && !$user->subscription && empty($user->trial_ends_at)) {
            \Log::info('APPLYING TRIAL', ['days' => $trialDays]);
            $sessionData['subscription_data'] = [
                'trial_period_days' => $trialDays,
            ];
        } else {
            \Log::warning('Trial NOT applied', [
                'reason' => $trialDays <= 0 ? 'trial_days_zero' : ($defaultTrialPlanId != $plan->id ? 'not_trial_plan' : ($user->subscription ? 'has_subscription' : 'already_used_trial'))
            ]);
        }

        $checkoutSession = $stripe->checkout->sessions->create($sessionData);

        return redirect()->to($checkoutSession->url);
    })->name('checkout.quick');
});

// Test route for AI debug button
Route::get('/error/test', function () {
    // Trigger an intentional error for testing
    throw new \Exception('Test error for AI debug button - This is a demo error!');
})->middleware('web');

// Data deletion request route (for Facebook App compliance)
Route::get('/settings/data-deletion', App\Livewire\Settings\DataDeletionRequest::class)
    ->name('settings.data-deletion');

// Stripe webhook alias route (for backward compatibility with ngrok setup)
Route::post('stripe/webhook', '\Wave\Http\Controllers\Billing\Webhooks\StripeWebhook@handler');

// Webhook test endpoint - verify webhooks can reach server
Route::match(['get', 'post'], 'stripe/webhook/test', function(\Illuminate\Http\Request $request) {
    \Log::info('Webhook test endpoint hit', [
        'method' => $request->method(),
        'ip' => $request->ip(),
        'headers' => $request->headers->all(),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Webhook endpoint is accessible',
        'timestamp' => now()->toDateTimeString(),
        'ip' => $request->ip(),
    ]);
});

// Stripe Customer Portal redirect
Route::get('stripe/portal', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('login');
    }

    // Get user's subscription
    $subscription = \Wave\Subscription::where('billable_id', $user->id)
        ->where('billable_type', 'user')
        ->where('status', 'active')
        ->first();

    if (!$subscription || !$subscription->vendor_customer_id) {
        return redirect()->route('settings.subscription')->with('error', 'No active subscription found.');
    }

    try {
        // Use StripeService to get correct credentials (respects test/live mode)
        $stripeService = app(\App\Services\StripeService::class);

        if (!$stripeService->isConfigured()) {
            return redirect()->route('settings.subscription')->with('error', 'Stripe is not configured.');
        }

        $stripe = new \Stripe\StripeClient($stripeService->getSecretKey());

        // Create a billing portal session
        $session = $stripe->billingPortal->sessions->create([
            'customer' => $subscription->vendor_customer_id,
            'return_url' => url('/settings/subscription'),
        ]);

        return redirect($session->url);
    } catch (\Exception $e) {
        \Log::error('Stripe portal error: ' . $e->getMessage());
        return redirect()->route('settings.subscription')->with('error', 'Unable to access billing portal.');
    }
})->middleware('auth')->name('stripe.portal');

// Growth Hacking routes (public)
Route::get('/welcome/setup-password/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'welcome'])->name('growth-hack.welcome');
Route::post('/welcome/set-password', [\App\Http\Controllers\GrowthHackingController::class, 'setPassword'])->name('growth-hack.set-password');
Route::get('/unsubscribe/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'unsubscribe'])->name('growth-hack.unsubscribe');
Route::post('/unsubscribe/process', [\App\Http\Controllers\GrowthHackingController::class, 'processUnsubscribe'])->name('growth-hack.unsubscribe.process');
Route::get('/track/open/{token}', [\App\Http\Controllers\GrowthHackingController::class, 'trackOpen'])->name('growth-hack.track-open');
Route::get('/track/click', [\App\Http\Controllers\GrowthHackingController::class, 'trackClick'])->name('growth-hack.track-click');

// Marketplace routes
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    // Public routes
    Route::get('/', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('index');
    Route::get('/category/{category}', [\App\Http\Controllers\MarketplaceController::class, 'category'])->name('category');
    Route::get('/item/{item}', [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('show');

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Download
        Route::get('/item/{item}/download', [\App\Http\Controllers\MarketplaceController::class, 'download'])->name('download');

        // Purchases
        Route::get('/purchases', [\App\Http\Controllers\MarketplacePurchaseController::class, 'index'])->name('purchases.index');
        Route::post('/item/{item}/purchase', [\App\Http\Controllers\MarketplacePurchaseController::class, 'initiate'])->name('purchase.initiate');
        Route::get('/purchase/success', [\App\Http\Controllers\MarketplacePurchaseController::class, 'success'])->name('purchase.success');
        Route::get('/purchase/{purchase}/refund', [\App\Http\Controllers\MarketplacePurchaseController::class, 'refundRequest'])->name('purchase.refund');
        Route::post('/purchase/{purchase}/refund', [\App\Http\Controllers\MarketplacePurchaseController::class, 'refundProcess'])->name('purchase.refund.process');

        // Creator dashboard
        Route::prefix('creator')->name('creator.')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\MarketplaceCreatorController::class, 'dashboard'])->name('dashboard');
            Route::get('/items/create', [\App\Http\Controllers\MarketplaceCreatorController::class, 'create'])->name('create');
            Route::post('/items', [\App\Http\Controllers\MarketplaceCreatorController::class, 'store'])->name('store');
            Route::get('/items/{item}/edit', [\App\Http\Controllers\MarketplaceCreatorController::class, 'edit'])->name('edit');
            Route::put('/items/{item}', [\App\Http\Controllers\MarketplaceCreatorController::class, 'update'])->name('update');
            Route::delete('/items/{item}', [\App\Http\Controllers\MarketplaceCreatorController::class, 'destroy'])->name('destroy');
            Route::post('/items/{item}/submit', [\App\Http\Controllers\MarketplaceCreatorController::class, 'submitForReview'])->name('submit');
            Route::get('/items/{item}/analytics', [\App\Http\Controllers\MarketplaceCreatorController::class, 'analytics'])->name('analytics');
        });

        // Payouts
        Route::prefix('payouts')->name('payouts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\MarketplacePayoutController::class, 'index'])->name('index');
            Route::get('/request', [\App\Http\Controllers\MarketplacePayoutController::class, 'create'])->name('create');
            Route::post('/request', [\App\Http\Controllers\MarketplacePayoutController::class, 'store'])->name('store');
            Route::get('/{payout}', [\App\Http\Controllers\MarketplacePayoutController::class, 'show'])->name('show');
            Route::post('/{payout}/cancel', [\App\Http\Controllers\MarketplacePayoutController::class, 'cancel'])->name('cancel');
            Route::post('/profile/update', [\App\Http\Controllers\MarketplacePayoutController::class, 'updateProfile'])->name('profile.update');
        });
    });
});

// Wave routes
Wave::routes();

// NUMZ.AI Hosting Billing Routes
Route::middleware(['web', 'auth'])->prefix('numz')->name('numz.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function() {
        return view('numz.dashboard');
    })->name('dashboard');

    // Services
    Route::get('/services', function() {
        $services = \App\Models\HostingService::where('user_id', auth()->id())->get();
        return view('numz.services.index', compact('services'));
    })->name('services.index');

    // Domains
    Route::get('/domains', function() {
        $domains = \App\Models\DomainRegistration::where('user_id', auth()->id())->get();
        return view('numz.domains.index', compact('domains'));
    })->name('domains.index');

    // Billing
    Route::get('/invoices', function() {
        return view('numz.invoices.index');
    })->name('invoices.index');

    // Support
    Route::get('/support', function() {
        return view('numz.support.index');
    })->name('support.index');
});

// WHMCS Compatibility API
Route::prefix('api/whmcs')->name('whmcs.api.')->group(function () {
    Route::post('/client/details', function(\Illuminate\Http\Request $request) {
        $compat = new \App\Numz\Services\WHMCSCompatibility();
        return response()->json($compat->getClientDetails($request->input('clientid')));
    });

    Route::post('/client/services', function(\Illuminate\Http\Request $request) {
        $compat = new \App\Numz\Services\WHMCSCompatibility();
        return response()->json($compat->getClientServices($request->input('clientid')));
    });

    Route::post('/client/domains', function(\Illuminate\Http\Request $request) {
        $compat = new \App\Numz\Services\WHMCSCompatibility();
        return response()->json($compat->getClientDomains($request->input('clientid')));
    });
});
