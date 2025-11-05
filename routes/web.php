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
