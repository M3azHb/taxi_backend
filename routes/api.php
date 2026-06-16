<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer as Customer;
use App\Http\Controllers\Api\Driver as Driver;

Route::middleware('auth:sanctum')->group(function () {

    // 1. مسارات الزبون (Customer)
    Route::prefix('customer')->group(function () {
        Route::get('drivers/available', [Customer\DriverBrowseController::class, 'available']);
        Route::get('drivers/{id}', [Customer\DriverBrowseController::class, 'show']);
        Route::post('discount/validate', [Customer\DiscountCodeController::class, 'validateCode']);
        Route::apiResource('blocks', Customer\BlockListController::class);
        Route::apiResource('reports', Customer\ReportController::class);

        // مسارات الرحلات الخاصة بالزبون
        Route::post('rides/estimate', [Customer\RideController::class, 'estimate']);
        Route::post('rides', [Customer\RideController::class, 'store']);
        Route::get('rides/active', [Customer\RideController::class, 'active']);
        Route::get('rides/{id}', [Customer\RideController::class, 'show']);
        Route::get('rides', [Customer\RideController::class, 'index']);
        Route::post('rides/{id}/cancel', [Customer\RideController::class, 'cancel']);
        Route::post('rides/{id}/rate', [Customer\RatingController::class, 'rate']);

        // الإشعارات
        Route::prefix('notifications')->group(function () {
            Route::get('/', [Customer\NotificationController::class, 'index']);
            Route::get('unread-count', [Customer\NotificationController::class, 'unreadCount']);
            Route::put('{id}/read', [Customer\NotificationController::class, 'markAsRead']);
            Route::put('read-all', [Customer\NotificationController::class, 'markAllAsRead']);
        });
    });

    // 2. مسارات السائق (Driver)
    Route::prefix('driver')->group(function () {
        Route::apiResource('blocks', Driver\BlockListController::class);
        Route::apiResource('reports', Driver\ReportController::class);

        // المسارات التي تتطلب أن يكون السائق متاحاً (Available)
        Route::middleware('driver.available')->group(function () {
            Route::get('rides/pending', [Driver\RideController::class, 'pending']);
            Route::get('rides/active', [Driver\RideController::class, 'active']);
            Route::post('rides/{id}/accept', [Driver\RideController::class, 'accept']);
            Route::post('rides/{id}/reject', [Driver\RideController::class, 'reject']);
            Route::post('rides/{id}/arrive', [Driver\RideController::class, 'arrive']);
            Route::post('rides/{id}/start', [Driver\RideController::class, 'start']);
            Route::post('rides/{id}/complete', [Driver\RideController::class, 'complete']);
        });

        // مسارات الأرباح والإشعارات (لا تتطلب أن يكون متاحاً)
        Route::get('earnings/summary', [Driver\EarningsController::class, 'summary']);
        Route::get('earnings/chart', [Driver\EarningsController::class, 'chart']);

        Route::prefix('notifications')->group(function () {
            Route::get('/', [Driver\NotificationController::class, 'index']);
            Route::get('unread-count', [Driver\NotificationController::class, 'unreadCount']);
            Route::put('{id}/read', [Driver\NotificationController::class, 'markAsRead']);
            Route::put('read-all', [Driver\NotificationController::class, 'markAllAsRead']);
            Route::post('fcm-token', [Driver\NotificationController::class, 'storeFcmToken']);
        });
    });
});
