<?php

use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Api\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Api\Customer\CarTypeController;
use App\Http\Controllers\Api\Customer\BlockListController as CustomerBlockListController;
use App\Http\Controllers\Api\Customer\DiscountCodeController;
use App\Http\Controllers\Api\Customer\DriverBrowseController;
use App\Http\Controllers\Api\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Api\Customer\RatingController as CustomerRatingController;
use App\Http\Controllers\Api\Customer\ReportController as CustomerReportController;
use App\Http\Controllers\Api\Customer\RideController as CustomerRideController;
use App\Http\Controllers\Api\Driver\AuthController as DriverAuthController;
use App\Http\Controllers\Api\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Api\Driver\CarController as DriverCarController;
use App\Http\Controllers\Api\Driver\AvailabilityController;
use App\Http\Controllers\Api\Driver\LocationController;
use App\Http\Controllers\Api\Driver\BlockListController as DriverBlockListController;
use App\Http\Controllers\Api\Driver\EarningsController;
use App\Http\Controllers\Api\Driver\NotificationController as DriverNotificationController;
use App\Http\Controllers\Api\Driver\PaymentController;
use App\Http\Controllers\Api\Driver\RatingController as DriverRatingController;
use App\Http\Controllers\Api\Driver\ReportController as DriverReportController;
use App\Http\Controllers\Api\Driver\RideController as DriverRideController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication (Customer + Driver)
|--------------------------------------------------------------------------
*/

Route::prefix('customer/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('driver')->group(function () {
    Route::post('register', [DriverAuthController::class, 'register']);
    Route::post('login', [DriverAuthController::class, 'login']);
    Route::post('verify-otp', [DriverAuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [DriverAuthController::class, 'resendOtp']);
});

/*
|--------------------------------------------------------------------------
| Customer Routes (protected)
|--------------------------------------------------------------------------
*/
Route::prefix('customer')
    ->middleware(['auth:sanctum', 'verified.customer'])
    ->group(function () {

        // Auth (session)
        Route::post('logout', [CustomerAuthController::class, 'logout']);
        Route::get('me', [CustomerAuthController::class, 'me']);


        // Profile
        Route::get('profile', [CustomerProfileController::class, 'show']);
        Route::put('profile', [CustomerProfileController::class, 'update']);
        Route::put('profile/change-password', [CustomerProfileController::class, 'changePassword']);

        // Car Types (list for ride request)
        Route::get('car-types', [CarTypeController::class, 'index']);

        // Driver Browsing
        Route::prefix('drivers')->group(function () {
            Route::get('available', [DriverBrowseController::class, 'available']);
            Route::get('{id}', [DriverBrowseController::class, 'show']);
        });

        // Discount Codes
        Route::post('discount-codes/validate', [DiscountCodeController::class, 'validateCode']);

        // Block List
        Route::prefix('blocks')->group(function () {
            Route::get('/', [CustomerBlockListController::class, 'index']);
            Route::post('/', [CustomerBlockListController::class, 'store']);
            Route::delete('{driver_id}', [CustomerBlockListController::class, 'destroy']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('/', [CustomerReportController::class, 'index']);
            Route::post('/', [CustomerReportController::class, 'store']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [CustomerNotificationController::class, 'index']);
            Route::get('unread-count', [CustomerNotificationController::class, 'unreadCount']);
            Route::put('read-all', [CustomerNotificationController::class, 'markAllAsRead']);
            Route::put('{id}/read', [CustomerNotificationController::class, 'markAsRead']);
            Route::post('fcm-token', [CustomerNotificationController::class, 'storeFcmToken']);
        });

        // Rides
        Route::prefix('rides')->group(function () {
            Route::post('estimate', [CustomerRideController::class, 'estimate']);
            Route::get('active', [CustomerRideController::class, 'active']);
            Route::get('/', [CustomerRideController::class, 'index']);
            Route::post('/', [CustomerRideController::class, 'store']);
            Route::get('{id}', [CustomerRideController::class, 'show']);
            Route::put('{id}/cancel', [CustomerRideController::class, 'cancel']);
            Route::get('{id}/tracking', [CustomerRideController::class, 'tracking']);
            Route::post('{id}/rate', [CustomerRatingController::class, 'store']);
        });
    });

/*
|--------------------------------------------------------------------------
| Driver Routes (protected)
|--------------------------------------------------------------------------
*/
Route::prefix('driver')
    ->middleware(['auth:sanctum', 'verified.driver'])
    ->group(function () {

        // Auth (session)
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('me', [DriverAuthController::class, 'me']);

        // Profile
        Route::get('profile', [DriverProfileController::class, 'show']);
        Route::put('profile', [DriverProfileController::class, 'update']);

        // Car management
        Route::get('car-types', [DriverCarController::class, 'types']);
        Route::get('car', [DriverCarController::class, 'show']);
        Route::post('car', [DriverCarController::class, 'store']);
        Route::put('car', [DriverCarController::class, 'update']);

        // Availability + Location
        Route::put('availability', [AvailabilityController::class, 'update']);
        Route::post('location', [LocationController::class, 'update']);

        // Block List
        Route::prefix('blocks')->group(function () {
            Route::get('/', [DriverBlockListController::class, 'index']);
            Route::post('/', [DriverBlockListController::class, 'store']);
            Route::delete('{customer_id}', [DriverBlockListController::class, 'destroy']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('/', [DriverReportController::class, 'index']);
            Route::post('/', [DriverReportController::class, 'store']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [DriverNotificationController::class, 'index']);
            Route::get('unread-count', [DriverNotificationController::class, 'unreadCount']);
            Route::put('read-all', [DriverNotificationController::class, 'markAllAsRead']);
            Route::put('{id}/read', [DriverNotificationController::class, 'markAsRead']);
            Route::post('fcm-token', [DriverNotificationController::class, 'storeFcmToken']);
        });

        // Rides
        Route::prefix('rides')->group(function () {
            Route::get('pending', [DriverRideController::class, 'pending']);
            Route::get('active', [DriverRideController::class, 'active']);
            Route::get('/', [DriverRideController::class, 'index']);
            Route::get('{id}', [DriverRideController::class, 'show']);
            Route::put('{id}/accept', [DriverRideController::class, 'accept']);
            Route::put('{id}/reject', [DriverRideController::class, 'reject']);
            Route::put('{id}/arrived', [DriverRideController::class, 'arrived']);
            Route::put('{id}/start', [DriverRideController::class, 'start']);
            Route::put('{id}/complete', [DriverRideController::class, 'complete']);
            Route::put('{id}/cancel', [DriverRideController::class, 'cancel']);
            Route::post('{id}/tracking', [DriverRideController::class, 'tracking']);
            Route::put('{id}/payment/confirm', [PaymentController::class, 'confirm']);
        });

        // Payments
        Route::get('payments', [PaymentController::class, 'index']);

        // Earnings
        Route::prefix('earnings')->group(function () {
            Route::get('summary', [EarningsController::class, 'summary']);
            Route::get('chart', [EarningsController::class, 'chart']);
            Route::get('commission', [EarningsController::class, 'commission']);
        });

        // Ratings
        Route::prefix('ratings')->group(function () {
            Route::get('/', [DriverRatingController::class, 'index']);
            Route::get('summary', [DriverRatingController::class, 'summary']);
        });
    });
