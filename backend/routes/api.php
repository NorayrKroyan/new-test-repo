<?php

use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\CarrierController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\JobAvailableController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Carrier\AuthController as CarrierAuthController;
use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('/admin-users', AdminUserController::class);
        Route::apiResource('/leads', LeadController::class);
        Route::post('/leads/{lead}/convert-to-carrier', [LeadController::class, 'convertToCarrier']);

        Route::apiResource('/carriers', CarrierController::class);
        Route::apiResource('/customers', CustomerController::class);
        Route::apiResource('/jobs-available', JobAvailableController::class);
    });
});

Route::prefix('carrier')->group(function () {
    Route::post('/register', [CarrierAuthController::class, 'register']);
    Route::post('/login', [CarrierAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:carrier'])->group(function () {
        Route::post('/logout', [CarrierAuthController::class, 'logout']);
        Route::get('/me', [CarrierAuthController::class, 'me']);
        Route::get('/dashboard', [CarrierAuthController::class, 'dashboard']);
        Route::put('/profile', [CarrierAuthController::class, 'updateProfile']);
        Route::post('/change-password', [CarrierAuthController::class, 'changePassword']);
    });
});

Route::prefix('customer')->group(function () {
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/login', [CustomerAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/me', [CustomerAuthController::class, 'me']);
        Route::get('/dashboard', [CustomerAuthController::class, 'dashboard']);
        Route::put('/profile', [CustomerAuthController::class, 'updateProfile']);
        Route::post('/change-password', [CustomerAuthController::class, 'changePassword']);
    });
});
