<?php

namespace App\Http\Controllers\Instructors;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\CoursesRequest\CourseRequest;
use App\Http\Requests\CoursesRequest\StoreCourseRequest;
use App\Http\Resources\CourseResource;
use App\Services\CourseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use PHPUnit\TextUI\Help;

class InstructorCourseController extends Controller
{

    public function __construct( protected CourseService $courseService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::where('teacher_id', Auth::id())->get();
        return ApiResource::sendResponse("Courses retrieved successfully.", CourseResource::collection($courses));

    }




    public function store(CourseRequest $request)
    {
        $data=$request->validated();
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('course_covers', 'public');
           $data['cover_image'] = $coverImagePath;
        }
        $course = $this->courseService->createCourse($data, Auth::id());

        return ApiResource::sendResponse("Course created successfully.", new CourseResource($course),201);

    }

    /**
     * Display the specified course.
     */
    public function show(int $id)
    {
        //
        $course = Course::where('teacher_id', Auth::id())->findOrFail($id);

        return ApiResource::sendResponse("Course details retrieved successfully.", new CourseResource($course));
    }



    /**
     * Update the specified course in storage.
     */
    public function update()
    {


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        Gate::authorize('delete',$course);
        $course->delete();
        return ApiResource::sendResponse("Course deleted successfully.");
    }
}
