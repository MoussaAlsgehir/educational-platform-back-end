<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\NotificationResource;
use App\Models\Course;
use App\Notifications\GeneralNotification;
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

       public function enroll(Course $course)
    {
        $student = Auth::user();

        if (!$course->is_published || in_array($course->status, ['draft', 'pending', 'rejected', 'hidden'])) {
            return ApiResource::sendResponse("This course is not available for enrollment.", null, 400);
        }

        if ($student->courses()->where('course_id', $course->id)->exists()) {
            return ApiResource::sendResponse("You are already enrolled in this course.", null, 400);
        }

        $priceToDeduct = $course->getFinalPrice();

        if ($priceToDeduct == 0) {
            $student->courses()->attach($course->id, ['is_completed' => false]);
            return ApiResource::sendResponse("Enrolled successfully (Free Course)!");
        }


        try {
            DB::transaction(function () use ($student, $course, $priceToDeduct) {

                // أ. خصم النقاط من الطالب
                $this->walletService->deductPoints(
                    $student,
                    $priceToDeduct,
                    'course_purchase',
                    "Bought course: {$course->title}"
                );

                // ب. إضافة الأرباح للمدرس
                if ($course->teacher_id) {
                    $this->walletService->addPoints(
                        $course->teacher,
                        $priceToDeduct,
                        'course_earnings',
                        "Earnings from course: {$course->title}"
                    );
                }

                // ج. تفعيل الاشتراك
                $student->courses()->attach($course->id, [
                    'is_completed' => false,

                ]);
            });
             $student->notify(new GeneralNotification(
                'Course Enrolled',
                "You have successfully enrolled in the course: {$course->title}",
                'enrollment'
            ));
            return ApiResource::sendResponse("Enrolled successfully! Payment processed.");

        } catch (Exception $e) {
            return ApiResource::sendResponse($e->getMessage(), null, 400);
        }
    }

}
