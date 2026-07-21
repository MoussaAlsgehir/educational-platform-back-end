<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    public function pending()
    {
        $courses = Course::where('status', 'pending')->with('teacher')->latest()->get();
        return ApiResource::sendResponse("Pending courses retrieved.", $courses);
    }

    public function approve(Course $course)
    {
        $course->update([
            'status' => 'active',
            'rejection_reason' => null
        ]);
        return ApiResource::sendResponse("Course approved successfully.", $course);
    }

    public function reject(Request $request, Course $course)
    {
        $request->validate(['rejection_reason' => 'required|string']);

        $course->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        return ApiResource::sendResponse("Course rejected successfully.", $course);
    }
}
