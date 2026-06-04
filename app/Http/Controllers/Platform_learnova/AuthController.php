<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\Role;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\OTPVerifiedSuccessfullyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * تسجيل مستخدم جديد
     */
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        $validatedData['password'] = Hash::make($request->password);

        if (!empty($validatedData['date_of_birth'])) {
            $validatedData['date_of_birth'] = \Carbon\Carbon::parse($validatedData['date_of_birth'])->format('Y-m-d');
        }

        if ($request->hasFile('avatar_url')) {
            $file = $request->file('avatar_url');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $fileName, 'public');
            $validatedData['avatar_url'] = $path;
        } else {
            $validatedData['avatar_url'] = 'avatars/default-avatar.jpg';
        }

        $user = User::create($validatedData);

// إسناد الأدوار الافتراضية (طالب ومدرس)
        $defaultRoles = Role::whereIn('name', ['student', 'instructor'])->pluck('id')->toArray();
        $user->roles()->attach($defaultRoles);

        $otpCode = (string) rand(100000, 999999);
        Otp::updateOrCreate(
            ['user_id' => $user->id],
            ['code' => $otpCode, 'expires_at' => now()->addMinutes(10), 'is_used' => false]
        );

        Mail::to($user->email)->send(new OtpMail($otpCode));

        $data = ['user_information' => new UserResource($user)];

        return ApiResource::sendResponse("User registered successfully. Please check your email for verification.", $data);
    }

    /**
     * تسجيل الدخول المبدئي (طلب الـ OTP)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            $otpCode = (string) rand(100000, 999999);
            Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'code'       => $otpCode,
                    'expires_at' => now()->addMinutes(10),
                    'is_used'    => false,
                ]
            );

            Mail::to($user->email)->send(new OtpMail($otpCode));

            $data = ['user_information' => new UserResource($user)];

            return ApiResource::sendResponse("Login successful. Please check your email for OTP.", $data);
        }

        return ApiResource::sendResponse("Invalid credentials", null, 401);
    }

    /**
     * التحقق من كود الـ OTP (تفعيل الحساب وإصدار التوكن)
     */
    public function verificationCode(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        if ($otp) {
            $token = $user->createToken('auth_token')->plainTextToken;

            $otp->is_used = true;
            $otp->save();

            if (is_null($user->email_verified_at)) {
                $user->email_verified_at = now();
                $user->save();
            }

            $data = [
                'user_information' => new UserResource($user),
                'token'            => $token,
            ];

            // $user->notify(new GeneralNotification(
            //     "OTP Verified Successfully",
            //     "Your OTP has been verified successfully.",
            //     "auth_otp"
            // ));
            return ApiResource::sendResponse("OTP verified successfully", $data);
        }

        return ApiResource::sendResponse("Invalid or expired OTP", null, 400);
    }


    /**
     * تسجيل الخروج وإبطال التوكن الحالي
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResource::sendResponse("Logged out successfully");
    }

    /**
     * نسيت كلمة المرور: طلب رمز استعادة الحساب
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $otpCode = (string) rand(100000, 999999);

        Otp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code'       => $otpCode,
                'expires_at' => now()->addMinutes(10),
                'is_used'    => false,
            ]
        );

        Mail::to($user->email)->send(new OtpMail($otpCode));

        return ApiResource::sendResponse("OTP code sent to your email for password reset.", null);
    }

    /**
     * فحص كود الاستعادة (التأكد من صحة الرمز قبل التعديل)
     */
    public function checkOtpForgotPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        if (!$otp) {
            return ApiResource::sendResponse("Invalid or expired OTP code.", null, 400);
        }

        return ApiResource::sendResponse("OTP is valid. You can now reset your password.", null);
    }

    /**
     * تغيير كلمة المرور الفعلية واستهلاك الرمز
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'otp_code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp_code)
            ->where('expires_at', '>', now())
            ->where('is_used', false)
            ->first();

        if (!$otp) {
            return ApiResource::sendResponse("Session expired or invalid OTP. Please request a new code.", null, 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $otp->is_used = true;
        $otp->save();

        return ApiResource::sendResponse("Password has been reset successfully. You can now log in.", null);
    }
}
