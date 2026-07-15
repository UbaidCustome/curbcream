<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\QuickActionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [AuthController::class, 'sendResetOtp'])->name('forgot-password.send');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{id}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::post('/customers/{id}/ban', [CustomerController::class, 'ban'])->name('customers.ban');
        Route::post('/customers/{id}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');

        Route::get('/providers', [ProviderController::class, 'index'])->name('providers.index');
        Route::get('/providers/{id}', [ProviderController::class, 'show'])->name('providers.show');
        Route::post('/providers/{id}/document-status', [ProviderController::class, 'updateDocumentStatus'])->name('providers.document-status');
        Route::post('/providers/{id}/toggle-status', [ProviderController::class, 'toggleStatus'])->name('providers.toggle-status');
        Route::post('/providers/{id}/ban', [ProviderController::class, 'ban'])->name('providers.ban');
        Route::post('/providers/{id}/subscription', [ProviderController::class, 'updateSubscription'])->name('providers.subscription');
        Route::post('/providers/{id}/send-expiry-reminder', [ProviderController::class, 'sendExpiryReminder'])->name('providers.send-expiry-reminder');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{id}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{id}/reassign', [BookingController::class, 'reassign'])->name('bookings.reassign');

        Route::get('/disputes', [QuickActionController::class, 'disputes'])->name('disputes.index');
        Route::post('/disputes/{id}/resolve', [QuickActionController::class, 'resolveDispute'])->name('disputes.resolve');

        Route::get('/listings', [QuickActionController::class, 'listings'])->name('listings.index');
        Route::post('/listings/{id}/toggle-featured', [QuickActionController::class, 'toggleFeatured'])->name('listings.toggle-featured');

        Route::get('/plans', [QuickActionController::class, 'plans'])->name('plans.index');
        Route::post('/plans', [QuickActionController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{id}', [QuickActionController::class, 'updatePlan'])->name('plans.update');
    });
});
