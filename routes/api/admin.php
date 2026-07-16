<?php

use App\Http\Controllers\Admins\CategoryController;
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

        // Route::get('/student/{studentId}', 'getStudentCertificates');  // admin/certificates/student/{studentId} - جلب شهادات طالب
        // Route::get('/course/{courseId}', 'getCourseCertificates');     // admin/certificates/course/{courseId} - جلب شهادات كورس
        // Route::get('/{certificateId}', 'show');                        // admin/certificates/{id} - معلومات الشهادة
        // Route::get('/{certificateId}/download', 'downloadPdf');        // admin/certificates/{id}/download - تحميل PDF
        // Route::post('/{certificateId}/delete', 'delete');             // admin/certificates/{certificateId}/delete - حذف شهادة
    });
});
