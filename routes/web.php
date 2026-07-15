<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CommunicationController;
use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocationSettingsController;
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

        Route::get('/location-settings', [LocationSettingsController::class, 'index'])->name('location.index');
        Route::post('/location-settings', [LocationSettingsController::class, 'updateSettings'])->name('location.settings.update');
        Route::post('/location-settings/regions', [LocationSettingsController::class, 'storeRegion'])->name('location.regions.store');
        Route::put('/location-settings/regions/{id}', [LocationSettingsController::class, 'updateRegion'])->name('location.regions.update');
        Route::post('/location-settings/regions/{id}/toggle', [LocationSettingsController::class, 'toggleRegion'])->name('location.regions.toggle');
        Route::delete('/location-settings/regions/{id}', [LocationSettingsController::class, 'destroyRegion'])->name('location.regions.destroy');

        Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
        Route::post('/communication/notifications', [CommunicationController::class, 'sendNotification'])->name('communication.notifications.send');
        Route::post('/communication/emails', [CommunicationController::class, 'sendEmail'])->name('communication.emails.send');
        Route::post('/communication/automation', [CommunicationController::class, 'updateAutomation'])->name('communication.automation.update');

        Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance.index');
        Route::post('/compliance/reviews/{id}/flag', [ComplianceController::class, 'flagReview'])->name('compliance.reviews.flag');
        Route::post('/compliance/reviews/{id}/remove', [ComplianceController::class, 'removeReview'])->name('compliance.reviews.remove');
        Route::post('/compliance/reviews/{id}/respond', [ComplianceController::class, 'respondReview'])->name('compliance.reviews.respond');
        Route::post('/compliance/users/{id}/ban', [ComplianceController::class, 'banUser'])->name('compliance.users.ban');
        Route::post('/compliance/policies/{type}', [ComplianceController::class, 'updatePolicy'])->name('compliance.policies.update');

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

        Route::get('/access-control', [AccessControlController::class, 'index'])->name('access.index');
        Route::post('/access-control/members', [AccessControlController::class, 'storeMember'])->name('access.members.store');
        Route::put('/access-control/members/{id}', [AccessControlController::class, 'updateMember'])->name('access.members.update');
        Route::post('/access-control/policies/{type}', [AccessControlController::class, 'updatePolicy'])->name('access.policies.update');

        Route::get('/content', [ContentController::class, 'index'])->name('content.index');
        Route::put('/content/{type}', [ContentController::class, 'update'])->name('content.update');

        Route::get('/listings', [QuickActionController::class, 'listings'])->name('listings.index');
        Route::post('/listings/{id}/toggle-featured', [QuickActionController::class, 'toggleFeatured'])->name('listings.toggle-featured');

        Route::get('/plans', [QuickActionController::class, 'plans'])->name('plans.index');
        Route::post('/plans', [QuickActionController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{id}', [QuickActionController::class, 'updatePlan'])->name('plans.update');
    });
});
