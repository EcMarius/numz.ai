<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Client;
use App\Http\Controllers\PaymentController;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Client Area Routes
Route::prefix('client')->name('client.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [Client\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/services', [Client\ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}', [Client\ServiceController::class, 'show'])->name('services.show');
    Route::get('/invoices', [Client\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [Client\InvoiceController::class, 'show'])->name('invoices.show');
    Route::resource('tickets', Client\TicketController::class);
});

// Admin Area Routes
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('clients', Admin\ClientController::class);
    Route::resource('products', Admin\ProductController::class);
    Route::resource('invoices', Admin\InvoiceController::class);
    Route::resource('services', Admin\ServiceController::class);
});

// Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::post('/process/{gateway}', [PaymentController::class, 'process'])->name('process');
    Route::get('/callback/{gateway}', [PaymentController::class, 'callback'])->name('callback');
    Route::post('/webhook/{gateway}', [PaymentController::class, 'webhook'])->name('webhook');
});

// WHMCS Compatibility Routes
Route::prefix('whmcs-compat')->group(function () {
    Route::any('/api', function () {
        $command = request('action');
        $result = localAPI($command, request()->except('action'));
        return response()->json($result);
    });
});
