<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SecuritySettingsController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security Routes (Example)
|--------------------------------------------------------------------------
|
| Add these routes to your routes/web.php or create a separate
| routes/security.php file and include it in RouteServiceProvider.
|
*/

// Two-Factor Authentication Routes
Route::middleware('auth')->group(function () {
    Route::prefix('2fa')->name('2fa.')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::get('/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('recovery-codes');
        Route::post('/regenerate-recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-recovery-codes');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::get('/challenge', [TwoFactorController::class, 'challenge'])->name('challenge');
        Route::post('/verify', [TwoFactorController::class, 'verify'])->name('verify');
        Route::get('/settings', [TwoFactorController::class, 'settings'])->name('settings');
    });
});

// GDPR Routes
Route::middleware('auth')->group(function () {
    Route::prefix('gdpr')->name('gdpr.')->group(function () {
        Route::get('/', [GdprController::class, 'index'])->name('index');
        Route::get('/export', [GdprController::class, 'export'])->name('export');
        Route::post('/request-deletion', [GdprController::class, 'requestDeletion'])->name('request-deletion');
        Route::post('/cancel-deletion', [GdprController::class, 'cancelDeletion'])->name('cancel-deletion');
        Route::post('/update-consent', [GdprController::class, 'updateConsent'])->name('update-consent');
        Route::post('/accept-privacy-policy', [GdprController::class, 'acceptPrivacyPolicy'])->name('accept-privacy-policy');
        Route::post('/accept-terms', [GdprController::class, 'acceptTerms'])->name('accept-terms');
        Route::get('/consent-history', [GdprController::class, 'consentHistory'])->name('consent-history');
    });
});

// Admin Security Routes
Route::middleware(['auth', 'role:super-admin|admin'])->prefix('admin')->name('admin.')->group(function () {

    // Role & Permission Management
    Route::resource('roles', RolePermissionController::class);
    Route::get('permissions', [RolePermissionController::class, 'permissions'])->name('permissions.index');
    Route::get('permissions/create', [RolePermissionController::class, 'createPermission'])->name('permissions.create');
    Route::post('permissions', [RolePermissionController::class, 'storePermission'])->name('permissions.store');
    Route::post('roles/assign', [RolePermissionController::class, 'assignRole'])->name('roles.assign');
    Route::post('roles/remove', [RolePermissionController::class, 'removeRole'])->name('roles.remove');

    // Activity Logs
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
        Route::get('/export/data', [ActivityLogController::class, 'export'])->name('export');
        Route::get('/suspicious/list', [ActivityLogController::class, 'suspicious'])->name('suspicious');
        Route::post('/clean', [ActivityLogController::class, 'clean'])->name('clean');
    });

    // Audit Logs
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show');
        Route::get('/export/data', [AuditLogController::class, 'export'])->name('export');
        Route::get('/statistics/view', [AuditLogController::class, 'statistics'])->name('statistics');
        Route::post('/clean', [AuditLogController::class, 'clean'])->name('clean');
    });

    // Security Settings
    Route::prefix('security')->name('security.')->group(function () {
        Route::get('/settings', [SecuritySettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SecuritySettingsController::class, 'update'])->name('settings.update');
        Route::get('/ip-whitelist', [SecuritySettingsController::class, 'ipWhitelist'])->name('ip-whitelist');
        Route::put('/ip-whitelist', [SecuritySettingsController::class, 'updateIpWhitelist'])->name('ip-whitelist.update');
    });
});
