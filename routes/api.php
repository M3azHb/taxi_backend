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

        Route::prefix('notifications')->group(function () {
            Route::get('/', [Driver\NotificationController::class, 'index']);
            Route::get('unread-count', [Driver\NotificationController::class, 'unreadCount']);
            Route::put('{id}/read', [Driver\NotificationController::class, 'markAsRead']);
            Route::put('read-all', [Driver\NotificationController::class, 'markAllAsRead']);
            Route::post('fcm-token', [Driver\NotificationController::class, 'storeFcmToken']);
        });
    });
});

