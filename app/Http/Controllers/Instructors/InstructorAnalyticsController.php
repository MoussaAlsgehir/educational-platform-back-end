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
     * الإحصائيات الشاملة للمدرس (الأساسية + التسجيلات الشهرية + الإكمال)
     */
    public function summary(Request $request)
    {
        $instructorId = $request->user()->id;

        // 1. جلب كل كورسات المدرس
        $courseIds = Course::where('teacher_id', $instructorId)->pluck('id');
        $totalCourses = $courseIds->count();

        // 2. عدد الطلاب الكلي (إجمالي المسجلين بكل الكورسات)
        $totalStudents = DB::table('student_enrollments_course')
            ->whereIn('course_id', $courseIds)
            ->count();

        // 3. الأرباح الكلية
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

        // 5. عدد الطلاب المكتملين للكورسات (لكامل الوقت)
        $totalCompleted = DB::table('student_enrollments_course')
            ->whereIn('course_id', $courseIds)
            ->where('is_completed', 1)
            ->count();

        // 6. التسجيلات الشهرية (آخر 6 أشهر افتراضياً، أو حسب المطلوب)
        $months = $request->input('months', 6);
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $enrollments = DB::table('student_enrollments_course')
            ->select('created_at')
            ->whereIn('course_id', $courseIds)
            ->where('created_at', '>=', $startDate)
            ->get();

        // بناء مصفوفة الأشهر
        $monthlyEnrollments = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M'); // مثل: Mar, Apr, May

            // حساب عدد التسجيلات بهاد الشهر
            $count = $enrollments->filter(function ($enrollment) use ($date) {
                return Carbon::parse($enrollment->created_at)->isSameMonth($date);
            })->count();

            $monthlyEnrollments[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }

        // 7. دمج كلشي بالرد النهائي
        return ApiResource::sendResponse("Instructor analytics retrieved.", [
            'total_courses' => $totalCourses,
            'total_students' => $totalStudents,
            'total_earnings' => $totalEarnings,
            'reviews' => [
                'total' => $totalReviews,
                'average' => round($averageRating ?? 0, 1)
            ],
            'enrollments' => [
                'total_completed_lifetime' => $totalCompleted,
                'monthly_data' => $monthlyEnrollments
            ]
        ]);
    }
}
