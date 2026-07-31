<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * تسجيل الطالب في كورس مدفوع (أو مجاني)
     */
    public function enroll(Request $request,Course $course)
    {

        $student = $request->user('sanctum');

        // 1. التأكد إنو الكورس متاح للشراء (متاح ومنشور)
        if (!$course->is_published || in_array($course->status, ['draft', 'pending', 'rejected', 'hidden'])) {
            return ApiResource::sendResponse("This course is not available for enrollment.", null, 400);
        }

        // 2. التأكد إنو الطالب ما مشترك فيه مسبقاً
        if ($student->courses()->where('course_id', $course->id)->exists()) {
            return ApiResource::sendResponse("You are already enrolled in this course.", null, 400);
        }

        // 3. التأكد إنو الكورس مجاني
        if ($course->price == 0) {
            $student->courses()->attach($course->id, ['is_completed' => false]);
            return ApiResource::sendResponse("Enrolled successfully (Free Course)!");
        }

        // 4. إذا الكورس مدفوع: تنفيذ عملية الشراء المالية
        try {
            DB::transaction(function () use ($student, $course) {

                // أ. خصم المبلغ من محفظة الطالب
                $this->walletService->deductPoints(
                    $student,
                    $course->price,
                    'course_purchase',
                    "Bought course: {$course->title}"
                );

                // ب. إضافة الأرباح للمدرس
                if ($course->teacher_id) {
                    $this->walletService->addPoints(
                        $course->teacher,
                        $course->price,
                        'course_earnings',
                        "Earnings from course: {$course->title}"
                    );
                }

                // ج. تفعيل الاشتراك بالكورس للطالب
                $student->courses()->attach($course->id, [
                    'is_completed' => false,

                ]);
            });

            return ApiResource::sendResponse("Enrolled successfully! Payment processed.");

        } catch (Exception $e) {
            // إذا الرصيد غير كافي، الـ WalletService رح يرمي Exception وهون بترجعه
            return ApiResource::sendResponse($e->getMessage(), null, 400);
        }
    }
}
