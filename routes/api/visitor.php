<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Students\StudentCourseController;
use Illuminate\Support\Facades\Route;

 Route::prefix('student')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/courses', [StudentCourseController::class, 'index']);
    Route::get('/courses/category/{id}', [StudentCourseController::class, 'showByCategory']);
    Route::get('/courses/{id}', [StudentCourseController::class, 'show']);
});


