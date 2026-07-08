<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Students\StudentCourseController;
use App\Http\Controllers\Platform_learnova\QuizzController;
use App\Http\Controllers\Platform_learnova\QuestionController;
use App\Http\Controllers\Platform_learnova\QuizAttemptController;
use App\Http\Controllers\Students\CourseReviewController;
use Illuminate\Support\Facades\Route;

// مسارات الطلاب لاستعراض المحتوى التعليمي والتصنيفات والدورات المتاحة
Route::middleware('role:student,super_admin')->prefix('student')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/courses', [StudentCourseController::class, 'index']);
    Route::get('/courses/category/{id}', [StudentCourseController::class, 'showByCategory']);
    Route::get('/courses/{id}', [StudentCourseController::class, 'show']);

    Route::prefix('reviews')->controller(CourseReviewController::class)->group(function () {
        Route::post('/', 'store');                // student/reviews
        Route::post('{id}/delete', 'destroy'); // student/reviews/{id}/delete
    });
});

// استعراض الكويزات (متاح للجميع بمن فيهم الطلاب)
Route::middleware('role:student,instructor,super_admin,admin')->prefix('sections')->controller(QuizzController::class)->group(function () {
    Route::get('{sectionId}/quizzs', 'index');
    Route::prefix('quizzs/{quizz}')->group(function () {
        Route::get('/', 'show');
    });



    // ... داخل الـ group الخاص بالطالب أو بجانبه
});


Route::middleware('role:student,super_admin')->prefix('student/attempts')->controller(QuizAttemptController::class)->group(function () {
    Route::get('/', 'index');        // استعراض سجل المحاولات
    Route::post('/', 'store');       // تسجيل محاولة جديدة
    Route::get('/quiz/{quizId}', 'getAttemptsByQuiz'); // استعراض جميع محاولات الطالب لاختبار معين
    Route::get('{id}', 'show');      // استعراض نتيجة محاولة معينة
    Route::post('{id}/delete', 'destroy')->withoutMiddleware('role')->middleware('role:super_admin');
});

// استعراض الأسئلة بشكل منفصل (متاح للطلاب والآدمن فقط كما هو محدد مسبقاً بكودك)
Route::prefix('questions')->controller(QuestionController::class)->group(function () {
    Route::get('/', 'index')->middleware('role:student,admin');
    Route::get('{id}', 'show')->middleware('role:student,admin');
});
