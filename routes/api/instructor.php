<?php

use App\Http\Controllers\Instructors\InstructorCourseController;
use App\Http\Controllers\Instructors\SectionController;
use App\Http\Controllers\Instructors\LessonController;
use App\Http\Controllers\Instructors\LessonContentController;
use App\Http\Controllers\Instructors\ChunkUploadController;
use App\Http\Controllers\Instructors\CourseAttachmentController;
use App\Http\Controllers\Instructors\WithdrawalController;
use App\Http\Controllers\Platform_learnova\QuizzController;
use App\Http\Controllers\Platform_learnova\QuestionController;
use Illuminate\Support\Facades\Route;

// مسارات المدربين الأساسية
Route::middleware('role:instructor,super_admin,admin')->prefix('instructor')->group(function () {
    // الكورسات
    Route::post('/courses', [InstructorCourseController::class, 'store']);
    Route::put('/courses/{id}',[InstructorCourseController::class, 'update']);
    Route::get('/courses', [InstructorCourseController::class, 'index']);
    Route::get('/courses/{id}', [InstructorCourseController::class, 'show']);
    Route::delete('/courses/{id}',[InstructorCourseController ::class, 'destroy']);

    // نشر الكورس
    Route::post('/courses/{course}/publish', [InstructorCourseController::class, 'publish']);

    // تقديم الكورس للمراجعة
    Route::post('courses/{course}/submit', [InstructorCourseController::class, 'submitForReview']);
    // الأقسام (Sections)
    Route::get('courses/{courseId}/sections',     [SectionController::class, 'index']);
    Route::post('courses/{courseId}/sections',    [SectionController::class, 'store']);
    Route::get('courses/sections/{sectionId}',    [SectionController::class, 'show']);
    Route::put('courses/sections/{sectionId}',    [SectionController::class, 'update']);
    Route::delete('courses/sections/{sectionId}', [SectionController::class, 'destroy']);

    // الدروس (Lessons)
    Route::get('sections/{sectionId}/lessons', [LessonController::class, 'index']);
    Route::post('sections/{sectionId}/lessons', [LessonController::class, 'store']);
    Route::get('sections/lessons/{lessonId}', [LessonController::class, 'show']);
    Route::put('sections/lessons/{lessonId}', [LessonController::class, 'update']);
    Route::delete('sections/lessons/{lessonId}', [LessonController::class, 'destroy']);

    // محتوى الدروس
    Route::post('lessons/{lessonId}/contents', [LessonContentController::class, 'store']);
    Route::put('lessons/contents/{contentId}', [LessonContentController::class, 'update']);
    Route::delete('lessons/contents/{contentId}', [LessonContentController::class, 'destroy']);

    // رفع الفيديو مقسم (Chunk Upload)
    Route::get('lessons/{lessonId}/upload-vedio/progress', [ChunkUploadController::class, 'checkProgress']);
    Route::post('lessons/{lessonId}/upload-vedio', [ChunkUploadController::class, 'uploadChunk']);

    // مرفقات الكورس (Course Attachments)
    Route::get('courses/attachments/{attachmentId}',  [CourseAttachmentController::class, 'show']);
    Route::post('courses/{courseId}/attachments', [CourseAttachmentController::class, 'store']);
    Route::delete('courses/attachments/{attachmentId}',   [CourseAttachmentController::class, 'destroy']);

    // مسارات طلبات السحب (Withdrawal Requests)
    Route::get('withdrawals', [WithdrawalController::class, 'index']);
    Route::post('withdrawals', [WithdrawalController::class, 'store']);
});

// مسارات الـ Quizzs الخاصة بالمدربين والإدارة والتعديل عليها
Route::middleware('role:instructor,super_admin,admin')->prefix('sections')->controller(QuizzController::class)->group(function () {
    Route::post('{sectionId}/quizzs', 'store');
    Route::prefix('quizzs/{quizz}')->group(function () {
        Route::post('/update', 'update');
        Route::post('/delete', 'destroy');
    });
});

// مسارات الـ Questions (التعديل، الإضافة، الحذف يخص المدرب والـ Super Admin)
Route::prefix('questions')->controller(QuestionController::class)->group(function () {
    Route::post('/', 'store');
    Route::post('{id}/update', 'update');
    Route::post('{id}/delete', 'destroy');
})->middleware('role:instructor,super_admin');
