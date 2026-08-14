<?php

namespace App\Http\Controllers\Instructors;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use App\Models\Transaction;
use App\Models\CourseReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InstructorAnalyticsController extends Controller
{
    /**
     * الإحصائيات العامة للمدرس
     */
    public function summary(Request $request)
    {
        $instructorId = $request->user()->id;

        // 1. عدد الكورسات الكلي
        $totalCourses = Course::where('teacher_id', $instructorId)->count();

        // جلب كل كورسات المدرس (للاستخدام بالاستعلامات الجاية)
        $courseIds = Course::where('teacher_id', $instructorId)->pluck('id');

        // 2. عدد الطلاب الكلي (إجمالي المسجلين بكل الكورسات)
        $totalStudents = DB::table('student_enrollments_course')
            ->whereIn('course_id', $courseIds)
            ->count();

        // 3. الأرباح الكلية (إجمالي النقاط المضافة للمحفظة كأرباح كورسات)
        $wallet = $request->user()->wallet;
        $totalEarnings = 0;
        if ($wallet) {
            $totalEarnings = Transaction::where('wallet_id', $wallet->id)
                ->where('transaction_type', 'course_earnings')
                ->sum('amount');
        }

        // 4. التقييمات الكلية
        $totalReviews = CourseReview::whereIn('course_id', $courseIds)->count();
        $averageRating = CourseReview::whereIn('course_id', $courseIds)->avg('rating');

        return ApiResource::sendResponse("Instructor summary retrieved.", [
            'total_courses' => $totalCourses,
            'total_students' => $totalStudents,
            'total_earnings' => $totalEarnings,
            'reviews' => [
                'total' => $totalReviews,
                'average' => round($averageRating ?? 0, 1)
            ]
        ]);
    }

    /**
     * إحصائيات التسجيل خلال آخر X شهر
     */
    public function enrollments(Request $request)
    {
        $instructorId = $request->user()->id;

        // عدد الأشهر (لو ما بعتها الفرونت إند، افترضنا 6 أشهر)
        $months = $request->input('months', 6);

        $courseIds = Course::where('teacher_id', $instructorId)->pluck('id');
        $startDate = Carbon::now()->subMonths($months);

        // جلب المسجلين بالكورسات خلال الفترة المحددة
        $enrollments = DB::table('student_enrollments_course')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalEnrollments = $enrollments->count();
        $completed = $enrollments->where('is_completed', 1)->count();
        $notCompleted = $enrollments->where('is_completed', 0)->count();

        return ApiResource::sendResponse("Enrollment analytics retrieved.", [
            'period_months' => (int) $months,
            'total_enrollments' => $totalEnrollments,
            'completed' => $completed,
            'not_completed' => $notCompleted
        ]);
    }
}
