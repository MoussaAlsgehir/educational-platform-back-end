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

        if (isset($validatedData['avatar_url'])) {
            $file = $request->file('avatar_url');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $fileName, 'public');
            $validatedData['avatar_url'] = $path;
        } else {
            $validatedData['avatar_url'] = 'avatars/default-avatar.jpg';
        }

        $user = User::create($validatedData);

        // إسناد الأدوار الافتراضية (طالب ومدرس)
        $defaultRoles = Role::where('name', 'student')->pluck('id');
        $user->roles()->attach($defaultRoles);

        $otpCode = (string) rand(100000, 999999);
        Otp::updateOrCreate(
            ['user_id' => $user->id],
            ['code' => $otpCode, 'expires_at' => now()->addMinutes(10), 'is_used' => false]
        );

        Mail::to($user->email)->send(new OtpMail($otpCode));

        // Notify user (welcome / OTP sent)
        try {
            $user->notifyGeneral(
                'مرحباً بك في Learnova',
                'تم إنشاء حسابك بنجاح. راجع بريدك للتحقق إن لزم.',
                'auth_welcome'
            );
        } catch (\Exception $e) {
            // handled inside notifyGeneral
        }

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


        // 1. التحقق من صحة الإيميل والباسورد
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            if ($user->status_account === 'suspend') {
                return ApiResource::sendResponse("This account is suspend.Please contact support.", null, 403);
            }
            if ($user->roles()->whereIn('name', ['admin', 'super_admin'])->exists()) {

                // إصدار التوكن فوراً للأدمن دون الحاجة لـ OTP
                $token = $user->createToken('auth_token')->plainTextToken;
                $user->update(['status_account' => 'active']);
                $user->save();
                $data = [
                    'user_information' => new UserResource($user),
                    'token'            => $token,
                    'redirect_to'      => 'dashboard' // توجيه الفرونت إند للوحة التحكم
                ];

                return ApiResource::sendResponse("Welcome back Admin. Login successful.", $data);
            }

            // 3. إذا كان مستخدماً عادياً (طالب / مدرس) -> نطبق آلية الـ OTP الخاصة بك
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

            // Notify user that OTP was sent for login
            try {
                $user->notifyGeneral(
                    'رمز التحقق (OTP)',
                    'تم إرسال رمز التحقق إلى بريدك الإلكتروني. صلاحية الرمز 10 دقائق.',
                    'auth_otp_sent'
                );
            } catch (\Exception $e) {
            }

            $data = [
                'user_information' => new UserResource($user),
                'redirect_to'      => 'otp_verification' // توجيه الفرونت إند لصفحة الـ OTP
            ];

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

            $user->update(['status_account' => 'active']);
            $user->save();

            $data = [
                'user_information' => new UserResource($user),
                'token'            => $token,
            ];

            try {
                $user->notifyGeneral(
                    'تم تفعيل حسابك',
                    'تم التحقق من حسابك بنجاح. يمكنك الآن الدخول إلى المنصة.',
                    'auth_verified'
                );
            } catch (\Exception $e) {
                // ignore notification failure
            }
            return ApiResource::sendResponse("OTP verified successfully", $data);
        }

        return ApiResource::sendResponse("Invalid or expired OTP", null, 400);
    }


    /**
     * تسجيل الخروج وإبطال التوكن الحالي
     */
    public function logout(Request $request)
    {

        $user = Auth::user();
        $user->update(['status_account' => 'inActive']);
        $user->save();

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

        // Notify user that password reset OTP was sent
        try {
            $user->notifyGeneral(
                'رمز استعادة الحساب',
                'أُرسل رمز الاستعادة إلى بريدك — صالح لمدة 10 دقائق.',
                'password_reset_sent'
            );
        } catch (\Exception $e) {
        }

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

        // Notify user about successful password reset
        try {
            $user->notifyGeneral(
                'تم تغيير كلمة المرور',
                'تم تغيير كلمة المرور بنجاح. إن لم تكن أنت فبرجاء التواصل مع الدعم.',
                'password_reset_success'
            );
        } catch (\Exception $e) {
        }

        return ApiResource::sendResponse("Password has been reset successfully. You can now log in.", null);
    }

    public function changeRoleUser(Request $request)
    {

        if (!(Auth::user()->hasRole('student') && Auth::user()->hasRole('instructor'))) {

            return ApiResource::sendResponse('You can\'t access ,becouse not has role student or instructor.', null, 403);
        }
        $user = Auth::user();
        if ($user->current_role === 'student') {
            $user->current_role = 'instructor';
        }
        elseif ($user->current_role === 'instructor') {
            $user->current_role = 'student';
        }

        $user->save();
        return ApiResource::sendResponse('Change Current Role Successflly', null, 200);
    }
}
