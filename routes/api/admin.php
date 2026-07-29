<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Platform_learnova\AdminManagementController;
use App\Http\Controllers\Platform_learnova\RoleController;
use App\Http\Controllers\Platform_learnova\CertificateController;
use Illuminate\Support\Facades\Route;

// مسارات الإدارة المطلقة (Super Admin Only)
Route::middleware('role:super_admin')->group(function () {
    Route::controller(RoleController::class)->prefix('roles')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::prefix('{role}')->group(function () {
            Route::post('update', 'update');
            Route::post('destroy', 'destroy');
        });
    });

    Route::post('admins', [AdminManagementController::class, 'store']);
});

// مسارات الإدارة المشتركة (Super Admin & Admin)
Route::middleware('role:super_admin,admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);

    // مسارات الشهادات الإدارية
    Route::prefix('certificates')->controller(CertificateController::class)->group(function () {
        Route::post('/check', 'exists');                        // student/certificates/check - التحقق من وجود شهادة
    });

    Route::get('/dashboard', [DashboardController::class, 'getDashboardStatistics']);
});
