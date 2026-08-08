<?php

namespace App\Services\Dashboard;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardStatistics
{


    public function getTotalCourses()
    {

        return Course::count();
    }

    public function getTotalUsers()
    {
        return User::count();
    }

    public function getNewUserInThisMonth()
    {
        return User::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }
    public function getTotalRevenue()
    {

        $totalPointsSpent = Transaction::where('direction', 'debit')
            ->where('transaction_type', 'course_purchase')
            ->sum('amount');

        // نضربها بـ 2 ليرة (أرباح المنصة الصافية من كل نقطة)
        return $totalPointsSpent * 2;
    }

    public function popularCategory()
    {

        $popularCategory = CourseCategory::select('category_id')
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->with('category:id,name')
            ->first();

        return $popularCategory?->category?->name;
    }

    public function getMonthlySubscriptionsCourses()
    {
        return Enrollment::select(
            DB::raw("DATE_FORMAT(created_at, '%b') as month"), // إرجاع اسم الشهر مثل Jan, Feb
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('created_at', date('Y')) // تصفية للسنة الحالية فقط
            ->groupBy(DB::raw("MONTH(created_at)"), DB::raw("DATE_FORMAT(created_at, '%b')"))
            ->orderBy(DB::raw("MONTH(created_at)"))
            ->get();
    }

    public function getMonthlyNewUsers()
    {
        return User::select(
            DB::raw("DATE_FORMAT(created_at, '%b') as month"), // إرجاع اسم الشهر مثل Jan, Feb
            DB::raw('COUNT(*) as count')
        )
            ->whereYear('created_at', date('Y')) // تصفية للسنة الحالية فقط
            ->groupBy(DB::raw("MONTH(created_at)"), DB::raw("DATE_FORMAT(created_at, '%b')"))
            ->orderBy(DB::raw("MONTH(created_at)"))
            ->get();
    }


    public function getCategoryDistribution()
    {
        $categoryDistribution = CourseCategory::select('category_id', DB::raw('COUNT(*)  as count '))
            ->groupBy('category_id')->orderBy('count', 'desc')
            ->with('category:id,name')
            ->get();

        return $categoryDistribution->map(function ($item) {
            return [
                'category' => $item->category?->name,
                'count' => $item->count,
            ];
        });
    }

    public function getRecentCourses($limit = 5)
    {
        return Course::with([
            'teacher:id,first_name,last_name',
            'categories:id,name'
        ])
            ->withCount('students') // بتضيف حقل تلقائي اسمه students_count لكل كورس
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($course) {
                // 1. إخفاء حقل pivot من قائمة التصنيفات
                $course->categories->makeHidden('pivot');

                // 2. إخفاء الأسماء الفرعية للمدرس
                if ($course->teacher) {
                    $course->teacher->makeHidden(['first_name', 'last_name']);
                }

                // 3. تعيين enrolledCount وإخفاء الـ students_count الإفتراضي إذا حابب
                $course->enrolledCount = $course->students_count;
                $course->makeHidden('students_count');

                return $course;
            });
    }

    public function getModerationQueueCourse(){

        return Course::where('status','pending')->count();

    }

    public function getPublishedCourses(){

        return Course::where('is_published',true)->count();
        
    }
    }
