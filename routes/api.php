<?php

use App\Http\Controllers\Platform_learnova\AuthController;
use App\Http\Controllers\Platform_learnova\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 🔓 مسارات عامة (مفتوحة لجميع المستخدمين والزوار)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verificationCode']);

// 🔑 مسارات استعادة الحساب ونسيان كلمة المرور (عامة أيضاً)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/check-forgot-otp', [AuthController::class, 'checkOtpForgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// 🔒 مسارات محمية (تتطلب تسجيل الدخول وإرسال الـ Token عبر الـ Bearer Token)
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('profile')->group(function () {
        Route::get('/{id}', [ProfileController::class, 'show']);
        Route::post('/{id}', [ProfileController::class, 'update']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
