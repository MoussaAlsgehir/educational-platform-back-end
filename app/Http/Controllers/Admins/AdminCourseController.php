<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseStatusService;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    public function pending()
    {
        $courses = Course::where('status', 'pending')->with('teacher')->latest()->get();
        return ApiResource::sendResponse("Pending courses retrieved.", CourseResource::collection($courses));
    }

    public function approve(Course $course)
    {
        if($course->status != 'pending')
            return ApiResource::sendResponse("Only pending courses can be approved.", 400);
        $course->update([
            'status' => 'approved',
            'rejection_reason' => null
        ]);
        CourseStatusService::refresh($course);
        return ApiResource::sendResponse("Course approved successfully.", new CourseResource($course));
    }

    public function reject(Request $request, Course $course)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        if ($course->status !== 'pending') {
            return ApiResource::sendResponse("Only pending courses can be rejected.", 400);
        }

        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        return ApiResource::sendResponse("Course rejected successfully.", new CourseResource($course));
    }
}
