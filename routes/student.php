<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Students\StudentCourseController;
use App\Http\Controllers\Platform_learnova\QuizzController;
use App\Http\Controllers\Platform_learnova\QuestionController;
use Illuminate\Support\Facades\Route;

// مسارات الطلاب لاستعراض المحتوى التعليمي والتصنيفات والدورات المتاحة
Route::middleware('role:student,super_admin')->prefix('student')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/courses', [StudentCourseController::class, 'index']);
    Route::get('/courses/category/{id}', [StudentCourseController::class, 'showByCategory']);
    Route::get('/courses/{id}', [StudentCourseController::class, 'show']);
});

// استعراض الكويزات (متاح للجميع بمن فيهم الطلاب)
Route::middleware('role:student,instructor,super_admin,admin')->prefix('sections')->controller(QuizzController::class)->group(function () {
    Route::get('{sectionId}/quizzs', 'index');
    Route::prefix('quizzs/{quizz}')->group(function () {
        Route::get('/', 'show');
    });
});

// استعراض الأسئلة بشكل منفصل (متاح للطلاب والآدمن فقط كما هو محدد مسبقاً بكودك)
Route::prefix('questions')->controller(QuestionController::class)->group(function () {
    Route::get('/', 'index')->middleware('role:student,admin');
    Route::get('{id}', 'show')->middleware('role:student,admin');
});
