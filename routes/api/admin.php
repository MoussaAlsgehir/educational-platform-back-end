<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Platform_learnova\AdminManagementController;
use App\Http\Controllers\Platform_learnova\RoleController;
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
});
