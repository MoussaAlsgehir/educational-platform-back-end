<?php

namespace App\Http\Controllers\Instructors;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use App\Models\CourseDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DiscountController extends Controller
{
    // عرض كل طلبات الخصم لكورسات المدرس
    public function index()
    {
        $discounts = CourseDiscount::whereHas('course', function ($q) {
            $q->where('teacher_id', Auth::id());
        })->with('course:id,title')->latest()->get();

        return ApiResource::sendResponse("Discount requests retrieved.", $discounts);
    }

    // تقديم طلب خصم جديد
    public function store(Request $request, Course $course)
    {
        Gate::authorize('update', $course);

        $request->validate([
            'percentage' => 'required|numeric|min:1|max:100',
            'type' => 'required|in:permanent,limited',
            'starts_at' => 'required_if:type,limited|nullable|date|after:today',
            'ends_at' => 'required_if:type,limited|nullable|date|after:starts_at',
        ]);

        $existingPending = CourseDiscount::where('course_id', $course->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return ApiResource::sendResponse("You already have a pending discount request for this course.", null, 422);
        }

        $discount = $course->discounts()->create([
            'percentage' => $request->percentage,
            'type' => $request->type,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'status' => 'pending',
        ]);

        return ApiResource::sendResponse("Discount request submitted successfully.", $discount, 201);
    }

    // إلغاء طلب خصم (إذا لسا معلق)
    public function destroy(CourseDiscount $discount)
    {
        Gate::authorize('update', $discount->course);

        if ($discount->status !== 'pending') {
            return ApiResource::sendResponse("Cannot delete a processed discount request.", null, 400);
        }

        $discount->delete();
        return ApiResource::sendResponse("Discount request deleted successfully.");
    }
}
