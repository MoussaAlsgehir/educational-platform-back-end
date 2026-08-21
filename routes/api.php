<?php

use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Students\DownloadController;
use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Platform_learnova\NotificationController;
use App\Http\Controllers\Platform_learnova\ProfileController;
use App\Http\Controllers\Students\WalletController;
use Illuminate\Support\Facades\Route;

/* --- 1. مسارات الزوار غير المسجلين --- */
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/visitor.php';

Route::get('/download-link/{contentId}', [DownloadController::class, 'generateLink'])
      ->middleware(['auth:sanctum', 'role:student,super_admin']);
/* --- 2. مسارات المحمية بـ Sanctum (المشتركة) --- */
Route::middleware('auth:sanctum')->group(function () {


Route::post('/change-current-role',[AuthController::class, 'changeRoleUser']);
Route::get('/student/wallet/balance', [WalletController::class, 'balance']);

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


   // مسارات الطالب والمدرس
Route::get('/conversations', [ConversationController::class, 'index']);
// جلب شات كورس محدد
Route::get('/courses/{courseId}/chat', [ConversationController::class, 'getCourseChat']);
Route::post('/conversations/support', [ConversationController::class, 'storeSupport']);
Route::post('/conversations/complaints', [ConversationController::class, 'storeComplaint']);
Route::post('/conversations/ai-chat', [ConversationController::class, 'startAiChat']);
Route::get('/conversations/{id}/messages', [ConversationController::class, 'messages']);
Route::get('/conversations/{id}/pinned', [ConversationController::class, 'pinnedMessages']);

// مسارات الرسائل
Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
Route::put('/messages/{message}', [MessageController::class, 'update']);
Route::post('/messages/{message}/teacher-reply', [MessageController::class, 'teacherReply']);
Route::post('/messages/{message}/pin', [MessageController::class, 'pin']);
Route::post('/messages/{message}/like', [MessageController::class, 'toggleLike']);


    /* --- 3. استدعاء بقية الملفات المفصولة داخل الميدلواير المحمي --- */
    require __DIR__ . '/api/admin.php';
    require __DIR__ . '/api/instructor.php';
    require __DIR__ . '/api/student.php';


});
