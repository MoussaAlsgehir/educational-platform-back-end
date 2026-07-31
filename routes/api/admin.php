<?php

use App\Http\Controllers\Admins\AdminCourseController;
use App\Http\Controllers\Admins\AdminWalletController;
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
    Route::apiResource('categories', CategoryController::class);// مسارات CRUD للكورسات (الإدارة فقط)

    // 1. جلب كل الكورسات المعلقة (بانتظار المراجعة)
    Route::get('courses/pending', [AdminCourseController::class, 'pending']);

    // 2. الموافقة على كورس معين
    Route::post('courses/{course}/approve', [AdminCourseController::class, 'approve']);

    // 3. رفض كورس معين (لازم يبعت معه reason بالندي)
    Route::post('courses/{course}/reject', [AdminCourseController::class, 'reject']);

    // 4. مسارات إدارة المحفظة (Wallet Management)
     Route::post('wallets/top-up', [AdminWalletController::class, 'topUp']); // مسار شحن المحفظة للطالب
});
