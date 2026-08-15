<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Notifications\GeneralNotification;
use App\Services\CourseStatusService;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    public function pending()
    {
        $courses = Course::where('status', 'pending')->with('teacher', 'categories', 'sections.lessons.contents')->latest()->get();
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
          $course->teacher->notify(new GeneralNotification(
            "Course Approved!",
            "Your course {$course->title} has been approved. You can now publish it to students.",
            "course_approved"
        ));
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
            $course->teacher->notify(new GeneralNotification(
            "Course Rejected",
            "Your course {$course->title} was rejected. Reason: {$request->rejection_reason}",
            "course_rejected"
        ));
        return ApiResource::sendResponse("Course rejected successfully.", ['Rejection Reason' => $course->rejection_reason]);
    }



    /**
     * فتح أو إغلاق صلاحية التعديل للكورس (استثناء إداري)
     */
    public function toggleEdit(Course $course)
    {
        $course->is_editable = !$course->is_editable;
        $course->save();

        $status = $course->is_editable ? 'enabled' : 'disabled';
        return ApiResource::sendResponse("Course editing has been {$status} for the instructor.", $course);
    }

    /**
     * إخفاء أو إظهار الكورس
     */
    public function toggleVisibility(Course $course)
    {
        if ($course->status === 'hidden') {
        CourseStatusService::refresh($course);
            $msg = "Course is now visible.";
        }
        else {
            $course->status = 'hidden';
            $course->save();
            $msg = "Course is now hidden from public catalog.";
        }

        return ApiResource::sendResponse($msg, $course->refresh());
    }
}
