<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\CourseReview;
use App\Helpers\ApiResource;
use App\Http\Requests\Students\StoreReviewRequest;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class CourseReviewController extends Controller

{
    /**
     * جلب كافة مراجعات كورس معين المتوافقة مع بنية جدول الـ Users الحالي
     */
    public function index($courseId)
    {
        // 1. التحقق أولاً من وجود الكورس نفسه في قاعدة البيانات
        $courseExists = Course::where('id', $courseId)->exists();

        if (!$courseExists) {
            return ApiResource::sendResponse('Course not found.', null, 200);

            }

        // 2. إذا كان الكورس موجوداً، نجلب مراجعاته كالمعتاد
        $reviews = CourseReview::with(['student' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'avatar_url');

            }])
            ->where('course_id', $courseId)
            ->latest()
            ->get()
            ->map(function ($review) {
                if ($review->student) {
                    $review->student->append('name');
                }
                return $review;
            });

        return ApiResource::sendResponse('Course reviews retrieved successfully.', $reviews, 200);

        }
    /**
     * إضافة أو تحديث التقييم (Upsert)
     */
    public function store(StoreReviewRequest $request)
    {
        $validated = $request->validated();
        $studentId = Auth::id();

        $review = CourseReview::updateOrCreate(
            [
                'course_id'  => $validated['course_id'],
                'student_id' => $studentId
            ],
            [
                'rating' => $validated['rating'],
                'review_text' => $validated['review_text'] ?? null
            ]
        );

        $statusCode = $review->wasRecentlyCreated ? 201 : 200;
        $message    = $review->wasRecentlyCreated ? 'Review submitted successfully.' : 'Review updated successfully.';

        return ApiResource::sendResponse($message, $review, $statusCode);
    }

    /**
     * الحذف الآمن
     */
    public function destroy($id)
    {
        $review = CourseReview::where('id', $id)
            ->where('student_id', Auth::id())
            ->first();

        if (!$review) {
            return ApiResource::sendResponse('Review not found or access denied.', null, 403);
        }

        $review->delete();

        return ApiResource::sendResponse('Review deleted successfully.', null, 200);
    }
}
