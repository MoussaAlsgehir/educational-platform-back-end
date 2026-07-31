<?php

use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Platform_learnova\CertificateController;
use App\Http\Controllers\Students\StudentCourseController;
use Illuminate\Support\Facades\Route;

 Route::prefix('student')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/courses', [StudentCourseController::class, 'index']);
    Route::get('/courses/{id}', [StudentCourseController::class, 'show']);
});

Route::get('/verify/{serial_number}', [CertificateController::class, 'verifyBySerial']);
