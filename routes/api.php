<?php

use App\Http\Controllers\Platform_learnova\NotificationController;
use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Instructors\ChunkUploadController;
use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Platform_learnova\ProfileController;
use App\Http\Controllers\Platform_learnova\RoleController;
use App\Http\Controllers\Instructors\InstructorCourseController;
use App\Http\Controllers\Instructors\LessonContentController;
use App\Http\Controllers\Instructors\LessonController;
use App\Http\Controllers\Instructors\SectionController;
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

Route::prefix('notifications')->controller(NotificationController::class)->group(function(){
        Route::get('/','index');
        Route::post('/mark-as-read', 'markAsRead');
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
    Route::middleware('role:instructor,super_admin')->prefix('instructor')->group(function () {
        // مسارات المدرسين
        //course routes
        Route::post('/courses', [InstructorCourseController::class, 'store']);
        Route::get('/courses', [InstructorCourseController::class, 'index']);
        Route::get('/courses/{id}', [InstructorCourseController::class, 'show']);

        //section routes
            Route::get('courses/{courseId}/sections', [SectionController::class, 'index']);
            Route::post('courses/{courseId}/sections', [SectionController::class, 'store']);
            Route::get('courses/sections/{sectionId}', [SectionController::class, 'show']);
            Route::put('courses/sections/{sectionId}', [SectionController::class, 'update']);
            Route::delete('courses/sections/{sectionId}', [SectionController::class, 'destroy']);

            //lesson routes
            Route::get('sections/{sectionId}/lessons', [LessonController::class, 'index']);
            Route::post('sections/{sectionId}/lessons', [LessonController::class, 'store']);
            Route::get('sections/lessons/{lessonId}', [LessonController::class, 'show']);
            Route::put('sections/lessons/{lessonId}', [LessonController::class, 'update']);
            Route::delete('sections/lessons/{lessonId}', [LessonController::class, 'destroy']);

            //lesson content routes
            Route::post('lessons/{lessonId}/contents', [LessonContentController::class, 'store']);
            Route::put('lessons/contents/{contentId}', [LessonContentController::class, 'update']);
            Route::delete('lessons/contents/{contentId}', [LessonContentController::class, 'destroy']);

            //upload routes
            Route::get('lessons/{lessonId}/upload-vedio/progress', [ChunkUploadController::class, 'checkProgress']);
            Route::post('lessons/{lessonId}/upload-vedio', [ChunkUploadController::class, 'uploadChunk']);
        });

    // 6. مسارات الطلاب (Students)
    Route::middleware('role:student,super_admin')->prefix('student')->group(function () {
        // مسارات الطلاب
        Route::get('/categories', [CategoryController::class, 'index']);//عرض التصنيفات للطلاب
        Route::get('/courses', [StudentCourseController::class, 'index']);//عرض الدورات المتاحة للطلاب
        Route::get('/courses/category/{id}', [StudentCourseController::class, 'showByCategory']);//عرض الدورات حسب التصنيف
        Route::get('/courses/{id}', [StudentCourseController::class, 'show']);//عرض تفاصيل دورة معينة للطلاب
    });
});
