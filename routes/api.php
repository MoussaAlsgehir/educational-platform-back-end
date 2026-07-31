<?php

use App\Http\Controllers\Students\DownloadController;
use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Platform_learnova\NotificationController;
use App\Http\Controllers\Platform_learnova\ProfileController;
use Illuminate\Support\Facades\Route;

/* --- 1. مسارات الزوار غير المسجلين --- */
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/visitor.php';

Route::get('/download-link/{contentId}', [DownloadController::class, 'generateLink'])
      ->middleware(['auth:sanctum', 'role:student,super_admin']);
/* --- 2. مسارات المحمية بـ Sanctum (المشتركة) --- */
Route::middleware('auth:sanctum')->group(function () {

    // الملف الشخصي
    Route::prefix('profile')->group(function () {
        Route::get('/{id}', [ProfileController::class, 'show']);
        Route::post('/{id}', [ProfileController::class, 'update']);
    });

    // الإشعارات
    Route::prefix('notifications')->controller(NotificationController::class)->group(function(){
        Route::get('/', 'index');
        Route::post('/mark-as-read', 'markAsRead');
    });

    // تسجيل الخروج
    Route::post('/logout', [AuthController::class, 'logout']);

   // عرض كل الenpoint المتاحة لجميع الأدوار




    /* --- 3. استدعاء بقية الملفات المفصولة داخل الميدلواير المحمي --- */
    require __DIR__ . '/api/admin.php';
    require __DIR__ . '/api/instructor.php';
    require __DIR__ . '/api/student.php';


});
