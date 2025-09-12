<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\FavouriteController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/client-test', function (Request $request) {
    return view('client-test');
});
Route::get('auth/all-products', [AuthController::class, 'allProducts']);

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('signup', [AuthController::class, 'signup']);
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']); 
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::get('page/{slug}', [AuthController::class,'page']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('all-users', [AuthController::class, 'allUsers']);
        Route::get('get-user/{id}', [AuthController::class, 'getUser']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        Route::post('update-password', [AuthController::class, 'changePassword']);
        
        Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
        Route::post('/toggle-active', [AuthController::class, 'toggleActive']);
        Route::post('/toggle-notification', [AuthController::class, 'toggleNotification']);
        
        Route::post('/add-to-favourite', [FavouriteController::class, 'addToFavourite']);
        Route::get('/get-favourites', [FavouriteController::class, 'getFavourites']);
        Route::post('/post-review', [ReviewController::class, 'submitReview']);

        Route::post('add-product', [AuthController::class, 'addProduct']);
        Route::put('update-product/{id}', [AuthController::class, 'updateProduct']);
        Route::get('get-product/{id}', [AuthController::class, 'getProduct']);
        Route::delete('delete-product/{id}', [AuthController::class, 'deleteProduct']);
        Route::get('get-products-by-user/{userId}', [AuthController::class, 'getProductsByUser']);
        Route::post('get-drivers', [AuthController::class, 'getDrivers']);
        Route::get('driver/{id}', [AuthController::class, 'getDriverDetail']);
        Route::get('/get-driver/{driver_id}/rating', [ReviewController::class, 'getDriverRating']);
        Route::get('/get-reviews/{driver_id}', [ReviewController::class, 'getDriverReviews']);
        Route::post('/driver/update-location', [AuthController::class, 'updateLocation']);
        Route::post('/drivers/search', [AuthController::class, 'searchDrivers']);
        
        Route::post('/bookings/schedule', [BookingController::class, 'scheduleBooking']);
        Route::post('/bookings/choose', [BookingController::class, 'chooseTruckBooking']);
        Route::post('/bookings/instant', [BookingController::class, 'instantBooking']);

        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);
        Route::post('/bookings/driver-response', [BookingController::class, 'driverResponse']);
        Route::get('/bookings/{id}', [BookingController::class, 'getBookingDetail']);
        Route::get('/booking-history', [BookingController::class, 'getBookingHistory']);
        Route::get('/scheduled-bookings', [BookingController::class, 'getScheduledBookings']);
        
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read/{id}', [NotificationController::class, 'markAsRead']);
        
        
    });
});
    