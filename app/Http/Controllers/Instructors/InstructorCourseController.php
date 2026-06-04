<?php

namespace App\Http\Controllers\Instructors;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\CoursesRequest\StoreCourseRequest;
use App\Http\Resources\CourseResource;
use App\Services\CourseService;
use PHPUnit\TextUI\Help;

class InstructorCourseController extends Controller
{

    public function __construct( protected CourseService $courseService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::where('teacher_id', auth()->id)->get();
        return ApiResource::sendResponse("Courses retrieved successfully.", CourseResource::collection($courses));

    }




    public function store(StoreCourseRequest $request)
    {
        $course = $this->courseService->createCourse($request->validated(),auth()->id);

        return ApiResource::sendResponse("Course created successfully.", new CourseResource($course),201);

    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        //
        return ApiResource::sendResponse("Course details retrieved successfully.", new CourseResource($course));
    }



    /**
     * Update the specified course in storage.
     */
    public function update()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $course->delete();
        return ApiResource::sendResponse("Course deleted successfully.");
    }
}
