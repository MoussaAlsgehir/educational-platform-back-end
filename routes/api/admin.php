<?php

use App\Http\Controllers\Admins\AdminCourseController;
use App\Http\Controllers\Admins\AdminInstructorRequestController;
use App\Http\Controllers\Admins\AdminWalletController;
use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Platform_learnova\AdminManagementController;
use App\Http\Controllers\Platform_learnova\RoleController;
use App\Http\Controllers\Platform_learnova\CertificateController;
use App\Http\Controllers\Platform_learnova\UserController;
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

    Route::apiResource('categories', CategoryController::class); // مسارات CRUD للكورسات (الإدارة فقط)

    // 1. جلب كل الكورسات المعلقة (بانتظار المراجعة)
    Route::get('courses/pending', [AdminCourseController::class, 'pending']);

    // 2. الموافقة على كورس معين
    Route::post('courses/{course}/approve', [AdminCourseController::class, 'approve']);

    // 3. رفض كورس معين (لازم يبعت معه reason بالندي)
    Route::post('courses/{course}/reject', [AdminCourseController::class, 'reject']);

    // فتح/إغلاق التعديل
    Route::post('courses/{course}/toggle-edit', [AdminCourseController::class, 'toggleEdit']);

    // إخفاء/إظهار الكورس
    Route::post('courses/{course}/toggle-visibility', [AdminCourseController::class, 'toggleVisibility']);


    // 4. مسارات إدارة المحفظة (Wallet Management)
    Route::post('wallets/top-up', [AdminWalletController::class, 'topUp']); // مسار شحن المحفظة للطالب

     Route::get('withdrawals/pending', [AdminWalletController::class, 'pending']);

     Route::post('withdrawals/{withdrawal}/approve', [AdminWalletController::class, 'approve']);

     Route::post('withdrawals/{withdrawal}/reject', [AdminWalletController::class, 'reject']);

     Route::get('financial-report', [AdminWalletController::class, 'getFinancialReport']); // مسار جلب التقارير المالية (إجمالي المبالغ، الطلبات المعلقة، الطلبات المقبولة، الطلبات المرفوضة)

// استعراض المعاملات المالية مع الفلترة
    Route::get('wallets/transactions', [AdminWalletController::class, 'transactions']);

    Route::apiResource('categories', CategoryController::class);

    // مسارات الشهادات الإدارية
    Route::prefix('certificates')->controller(CertificateController::class)->group(function () {
        Route::post('/check', 'exists');                        // student/certificates/check - التحقق من وجود شهادة
    });

    Route::get('/dashboard', [DashboardController::class, 'getDashboardStatistics']);


    Route::prefix('users')->controller(UserController::class)->group(function () {

        Route::get('/', 'getUserById');
        Route::get('/all', 'getUsers');
        Route::post('/delete','destroy');
        Route::post('/change-role','changeStatusUser');
        Route::post('/update','update');
    });
    // مسارات الطلبات للمعلم
    Route::get('instructor-requests/pending', [AdminInstructorRequestController::class, 'pending']);

    Route::post('instructor-requests/{instructorRequest}/review', [AdminInstructorRequestController::class, 'review']);

    Route::get('instructor-requests', [AdminInstructorRequestController::class, 'index']);

});
