<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Platform_learnova\ProfileController;
use App\Http\Controllers\Platform_learnova\RoleController;
use App\Http\Controllers\Instructors\InstructorCourseController;
use App\Http\Controllers\Students\StudentCourseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. مسارات الزوار والمتصفحين فقط (Guest Only Routes)
|--------------------------------------------------------------------------
| تم تطبيق ميدلواير guest_api هنا لمنع المسجلين من تكرار طلبات الدخول أو إنشاء الحسابات
*/

Route::middleware('guest_api')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verificationCode']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/check-forgot-otp', [AuthController::class, 'checkOtpForgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});


/*
|--------------------------------------------------------------------------
| 2. مسارات محمية (تتطلب تسجيل دخول بـ Sanctum Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // الملف الشخصي
    Route::prefix('profile')->group(function () {
        Route::get('/{id}', [ProfileController::class, 'show']);
        Route::post('/{id}', [ProfileController::class, 'update']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // 3. مسارات الإدارة المطلقة (Super Admin Only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::post('/roles/{id}', [RoleController::class, 'update']);
        Route::post('/roles/{id}', [RoleController::class, 'destroy']);
    });

    // 4. مسارات الإدارة المشتركة (Super Admin & Admin)
    Route::middleware('role:super_admin,admin')->group(function () {
        // مسارات التحكم المشترك
        Route::apiResource('categories', CategoryController::class);
    });

    // 5. مسارات المدربين (Instructors)
    Route::middleware('role:instructor')->prefix('instructor')->group(function () {
        // مسارات المدرسين
        Route::post('/courses', [InstructorCourseController::class, 'store']);
        Route::get('/courses', [InstructorCourseController::class, 'index']);
        Route::get('/courses/{id}', [InstructorCourseController::class, 'show']);

        });

    // 6. مسارات الطلاب (Students)
    Route::middleware('role:student')->prefix('student')->group(function () {
        // مسارات الطلاب
        Route::get('/categories', [CategoryController::class, 'index']);//عرض التصنيفات للطلاب
        Route::get('/courses', [StudentCourseController::class, 'index']);//عرض الدورات المتاحة للطلاب
        Route::get('/courses/category/{id}', [StudentCourseController::class, 'showByCategory']);//عرض الدورات حسب التصنيف
        Route::get('/courses/{id}', [StudentCourseController::class, 'show']);//عرض تفاصيل دورة معينة للطلاب
    });
});
