<?php

use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Students\CourseReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest_api')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verificationCode']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/check-forgot-otp', [AuthController::class, 'checkOtpForgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/courses/{courseId}/reviews', [CourseReviewController::class, 'index']);
});
