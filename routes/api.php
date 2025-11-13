<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return auth()->user();
});

// Update user country (for onboarding) - uses web middleware to work with session auth
Route::middleware('web', 'auth')->post('/user/update-country', function (Request $request) {
    $request->validate([
        'country' => 'required|string|max:2'
    ]);

    $user = auth()->user();
    $user->country = $request->country;
    $user->save();

    return response()->json(['success' => true, 'message' => 'Country updated successfully']);
});

Wave::api();

// Posts Example API Route
Route::middleware('auth:api')->group(function () {
    Route::get('/posts', [\App\Http\Controllers\Api\ApiController::class, 'posts']);
});

/*
|--------------------------------------------------------------------------
| EvenLeads API Routes
|--------------------------------------------------------------------------
|
| These routes are protected by the api.key middleware which validates
| API keys from the X-API-Key header or api_key parameter.
|
*/

// Public auth endpoint (no middleware required)
Route::get('/v1/auth/validate', [\App\Http\Controllers\Api\AuthController::class, 'validate']);

/*
|--------------------------------------------------------------------------
| Browser Extension API Routes
|--------------------------------------------------------------------------
|
| These routes are protected by Laravel Sanctum for the browser extension.
|
*/

// Extension auth endpoints (public)
Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Public settings endpoint (for extension logo, etc.)
Route::get('/settings', function () {
    $logoPath = setting('site.logo');
    $logoUrl = null;

    if ($logoPath) {
        // Check if it starts with http:// or https://
        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            $logoUrl = $logoPath;
        } else {
            // Try to get public asset path
            $logoUrl = url('storage/' . str_replace('public/', '', $logoPath));
        }
    }

    return response()->json([
        'logo' => $logoUrl,
        'name' => setting('site.name', 'EvenLeads'),
    ])->header('Access-Control-Allow-Origin', '*')
      ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
      ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

// OPTIONS endpoints for CORS preflight
Route::options('/settings', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

Route::options('/stats', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/campaigns', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/leads', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

Route::options('/extension/campaigns/{id}/context', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
});

// Extension protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/user', [\App\Http\Controllers\Api\AuthController::class, 'user']);
    Route::get('/auth/subscription', [\App\Http\Controllers\Api\AuthController::class, 'subscription']);
    Route::get('/auth/validate-plan', [\App\Http\Controllers\Api\AuthController::class, 'validatePlan']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    // Stats (with explicit CORS headers for browser extensions)
    Route::get('/stats', function () {
        $controller = app(\App\Http\Controllers\Api\AuthController::class);
        $response = $controller->stats(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    // Campaigns (with CORS)
    Route::get('/campaigns', function () {
        $controller = app(\App\Http\Controllers\Api\CampaignController::class);
        $response = $controller->index(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    Route::get('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);

    // Leads (from extension) with CORS
    Route::get('/leads', function () {
        $controller = app(\App\Http\Controllers\Api\LeadController::class);
        $response = $controller->index(request());
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    Route::post('/campaigns/{campaignId}/leads', [\App\Http\Controllers\Api\LeadController::class, 'store']);
    Route::post('/campaigns/{campaignId}/leads/bulk', [\App\Http\Controllers\Api\LeadController::class, 'storeBulk']);
    Route::patch('/leads/{id}/status', [\App\Http\Controllers\Api\LeadController::class, 'updateStatus']);
    Route::delete('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'destroy']);

    // Extension helper endpoints
    Route::post('/extension/generate-search-terms', [\App\Http\Controllers\Api\ExtensionController::class, 'generateSearchTerms']);
    Route::post('/extension/validate-lead', [\App\Http\Controllers\Api\ExtensionController::class, 'validateLead']);
    Route::post('/extension/record-sync-start', [\App\Http\Controllers\Api\ExtensionController::class, 'recordSyncStart']);
    Route::get('/extension/validate-token', [\App\Http\Controllers\Api\ExtensionController::class, 'validateToken']);

    // Campaign context endpoint
    Route::get('/extension/campaigns/{id}/context', function ($id) {
        $controller = app(\App\Http\Controllers\Api\ExtensionController::class);
        $response = $controller->getCampaignContext($id);
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Accept');
    });

    // Schema management endpoints (for admin)
    Route::prefix('admin/schemas')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SchemaController::class, 'index']);
        Route::get('/missing', [\App\Http\Controllers\Api\SchemaController::class, 'missing']);
        Route::get('/{platform}/{pageType}', [\App\Http\Controllers\Api\SchemaController::class, 'show']);
        Route::get('/{platform}/{pageType}/export', [\App\Http\Controllers\Api\SchemaController::class, 'export']);
        Route::get('/{platform}/{pageType}/history', [\App\Http\Controllers\Api\SchemaController::class, 'history']);
        Route::post('/import', [\App\Http\Controllers\Api\SchemaController::class, 'import']);
        Route::post('/bulk-import', [\App\Http\Controllers\Api\SchemaController::class, 'bulkImport']);
        Route::post('/test-selector', [\App\Http\Controllers\Api\SchemaController::class, 'testSelector']);
        Route::post('/', [\App\Http\Controllers\Api\SchemaController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\SchemaController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\SchemaController::class, 'destroy']);
        Route::post('/clear-cache', [\App\Http\Controllers\Api\SchemaController::class, 'clearCache']);
        Route::post('/{platform}/{pageType}/rollback', [\App\Http\Controllers\Api\SchemaController::class, 'rollback']);
    });

    // Schema endpoints for extension (read-only)
    Route::get('/extension/schemas/{platform}/{pageType}', [\App\Http\Controllers\Api\SchemaController::class, 'show']);
});

// Protected API routes with plan limit enforcement
Route::middleware('api.key')->prefix('v1')->group(function () {

    // Campaign endpoints
    Route::get('/campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'index']);
    Route::get('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'show']);
    Route::post('/campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'store'])
        ->middleware('enforce.limits:campaigns');  // CHECK CAMPAIGN LIMIT
    Route::put('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'update']);
    Route::delete('/campaigns/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'destroy']);

    // Lead endpoints
    Route::get('/leads', [\App\Http\Controllers\Api\LeadController::class, 'index']);
    Route::get('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'show']);
    Route::delete('/leads/{id}', [\App\Http\Controllers\Api\LeadController::class, 'destroy']);
    Route::post('/leads/bulk-delete', [\App\Http\Controllers\Api\LeadController::class, 'bulkDestroy']);
    Route::patch('/leads/{id}/status', [\App\Http\Controllers\Api\LeadController::class, 'updateStatus']);

    // Sync endpoints
    Route::post('/sync/campaign/{id}', [\App\Http\Controllers\Api\SyncController::class, 'syncCampaign'])
        ->middleware('enforce.limits:manual_sync');  // CHECK MANUAL SYNC LIMIT
    Route::post('/sync/all', [\App\Http\Controllers\Api\SyncController::class, 'syncAll'])
        ->middleware('enforce.limits:manual_sync');  // CHECK MANUAL SYNC LIMIT
    Route::get('/sync/history/{id}', [\App\Http\Controllers\Api\SyncController::class, 'syncHistory']);
    Route::get('/sync/campaign/{campaignId}/running', [\App\Http\Controllers\Api\SyncController::class, 'getRunningSyncForCampaign']);
    Route::get('/sync/{syncId}', [\App\Http\Controllers\Api\SyncController::class, 'getSyncDetails']);

    // Account endpoints
    Route::get('/account/usage', [\App\Http\Controllers\Api\AccountController::class, 'usage']);
});

/*
|--------------------------------------------------------------------------
| REST API Routes (WHMCS-style)
|--------------------------------------------------------------------------
|
| These routes provide a complete REST API for managing clients, services,
| invoices, domains, tickets, and products. Protected by API key authentication
| with rate limiting and request logging.
|
*/

// Public authentication endpoints
Route::prefix('api/v1')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\ApiAuthController::class, 'login']);
    Route::post('/auth/register', [\App\Http\Controllers\Api\ApiAuthController::class, 'register']);
});

// Protected REST API routes - API Key Authentication with Rate Limiting
Route::prefix('api/v1')->middleware(['api.key', 'throttle:60,1', \App\Http\Middleware\ApiLogger::class])->group(function () {

    // API Key Management
    Route::get('/auth/keys', [\App\Http\Controllers\Api\ApiAuthController::class, 'getApiKeys']);
    Route::post('/auth/keys', [\App\Http\Controllers\Api\ApiAuthController::class, 'createApiKey']);
    Route::delete('/auth/keys/{id}', [\App\Http\Controllers\Api\ApiAuthController::class, 'deleteApiKey']);

    // Client Management
    Route::get('/clients', [\App\Http\Controllers\Api\ApiClientController::class, 'index']);
    Route::get('/clients/{id}', [\App\Http\Controllers\Api\ApiClientController::class, 'show']);
    Route::post('/clients', [\App\Http\Controllers\Api\ApiClientController::class, 'store']);
    Route::put('/clients/{id}', [\App\Http\Controllers\Api\ApiClientController::class, 'update']);
    Route::delete('/clients/{id}', [\App\Http\Controllers\Api\ApiClientController::class, 'destroy']);

    // Service Management
    Route::get('/services', [\App\Http\Controllers\Api\ApiServiceController::class, 'index']);
    Route::get('/services/{id}', [\App\Http\Controllers\Api\ApiServiceController::class, 'show']);
    Route::post('/services', [\App\Http\Controllers\Api\ApiServiceController::class, 'store']);
    Route::post('/services/{id}/activate', [\App\Http\Controllers\Api\ApiServiceController::class, 'activate']);
    Route::post('/services/{id}/suspend', [\App\Http\Controllers\Api\ApiServiceController::class, 'suspend']);
    Route::post('/services/{id}/terminate', [\App\Http\Controllers\Api\ApiServiceController::class, 'terminate']);
    Route::post('/services/{id}/upgrade', [\App\Http\Controllers\Api\ApiServiceController::class, 'upgrade']);

    // Invoice Management
    Route::get('/invoices', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'show']);
    Route::post('/invoices', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'store']);
    Route::post('/invoices/{id}/pay', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'pay']);
    Route::post('/invoices/{id}/cancel', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'cancel']);
    Route::get('/invoices/{id}/download', [\App\Http\Controllers\Api\ApiInvoiceController::class, 'download']);

    // Domain Management
    Route::get('/domains', [\App\Http\Controllers\Api\ApiDomainController::class, 'index']);
    Route::get('/domains/{id}', [\App\Http\Controllers\Api\ApiDomainController::class, 'show']);
    Route::post('/domains/register', [\App\Http\Controllers\Api\ApiDomainController::class, 'register']);
    Route::post('/domains/{id}/renew', [\App\Http\Controllers\Api\ApiDomainController::class, 'renew']);
    Route::post('/domains/{id}/transfer', [\App\Http\Controllers\Api\ApiDomainController::class, 'transfer']);
    Route::post('/domains/{id}/toggle-auto-renew', [\App\Http\Controllers\Api\ApiDomainController::class, 'toggleAutoRenew']);

    // Ticket Management
    Route::get('/tickets', [\App\Http\Controllers\Api\ApiTicketController::class, 'index']);
    Route::get('/tickets/{id}', [\App\Http\Controllers\Api\ApiTicketController::class, 'show']);
    Route::post('/tickets', [\App\Http\Controllers\Api\ApiTicketController::class, 'store']);
    Route::post('/tickets/{id}/reply', [\App\Http\Controllers\Api\ApiTicketController::class, 'reply']);
    Route::post('/tickets/{id}/close', [\App\Http\Controllers\Api\ApiTicketController::class, 'close']);
    Route::post('/tickets/{id}/reopen', [\App\Http\Controllers\Api\ApiTicketController::class, 'reopen']);

    // Product Catalog
    Route::get('/products', [\App\Http\Controllers\Api\ApiProductController::class, 'index']);
    Route::get('/products/{id}', [\App\Http\Controllers\Api\ApiProductController::class, 'show']);
});
