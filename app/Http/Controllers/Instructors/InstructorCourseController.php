<?php

namespace App\Http\Controllers\Instructors;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\CoursesRequest\CourseRequest;
use App\Http\Requests\CoursesRequest\StoreCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Lesson;
use App\Services\CourseService;
use App\Services\CourseStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        CourseStatusService::refresh($course);

        return ApiResource::sendResponse("Course details retrieved successfully.", new CourseResource($course));
    }


    public function update(CourseRequest $request, Course $course)
    {
        Gate::authorize('update', $course);

        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($course->cover_image && $course->cover_image !== 'course_covers/default.png') {
                Storage::disk('public')->delete($course->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('course_covers', 'public');
        }

        $updatedCourse = $this->courseService->updateCourse($course, $data);

        return ApiResource::sendResponse("Course updated successfully.", new CourseResource($updatedCourse));
    }


    public function destroy(Course $course)
    {
        Gate::authorize('delete',$course);
        $course->delete();
        return ApiResource::sendResponse("Course deleted successfully.");
    }


    public function publish(Course $course)
    {
        Gate::authorize('update', $course);

        if ($course->status !== 'approved') {
            return ApiResource::sendResponse("Course must be approved by admin before publishing.", null, 403);
        }

        if ($course->is_published) {
            return ApiResource::sendResponse("Course is already published.", null, 400);
        }


        if ($course->publish_type === 'live') {
            // 1. للـ Live: القسم الأول لازم يكون كامل وفيه كويز
            $firstSection = $course->sections()->orderBy('order', 'asc')->first();
            if (!$firstSection || !$firstSection->lessons()->has('contents')->count() || !$firstSection->quiz) {
                return ApiResource::sendResponse("Cannot publish Live course: The first section must have complete lessons and a quiz.", null, 422);
            }
        } else {
            // 2. للـ On-Demand: إذا sequential و quiz_based، كل قسم لازم يكون فيه كويز
            if ($course->course_type === 'quiz_based' ) {
                $sections = $course->sections()->orderBy('order', 'asc')->get();
                foreach ($sections as $section) {
                    if (!$section->quiz) {
                        return ApiResource::sendResponse(
                            "Cannot publish: Section '{$section->title}' must have a quiz for sequential unlocking.",
                            null, 422
                        );
                    }
                }
            }
        }

        $course->is_published = true;
        $course->save();

        CourseStatusService::refresh($course);

        return ApiResource::sendResponse("Course published successfully.", new CourseResource($course->refresh()));
    }

    public function submitForReview(Course $course)
    {

        Gate::authorize('update', $course);


        if ($course->status !== 'draft') {
            return ApiResource::sendResponse("Course must be in draft state to submit for review.", null, 400);
        }


        $actualSectionsCount = $course->sections()->count();

        if ($course->expected_sections_count === 0) {
            return ApiResource::sendResponse("Please specify the expected number of sections for this course.", null, 422);
        }

        if ($actualSectionsCount !== $course->expected_sections_count) {
            return ApiResource::sendResponse(
                "You planned to create {$course->expected_sections_count} sections, but you only added {$actualSectionsCount}. Please complete the sections before submitting.",
                null,
                422
            );
        }


        // 4. المحتوى البريفيو
        $previewLessons = Lesson::whereHas('section', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->where('is_preview', true)->get();

        foreach ($previewLessons as $previewLesson) {
            if ($previewLesson->contents()->count() === 0) {
                return ApiResource::sendResponse(
                    "Cannot submit: The preview lesson '{$previewLesson->title}' must have at least one content (Video, PDF, or Text).",
                    null,
                    422
                );
            }
        }


        if ($course->publish_type === 'live') {

            $previewCount = $previewLessons->count();

            if ($previewCount < 1 || $previewCount > 3) {
                return ApiResource::sendResponse("Live courses must have between 1 and 3 preview videos.", null, 422);
            }
        }
        // on demand
        else {
            $lessonsCount = Lesson::whereHas('section', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })->has('contents')->count();

            if ($lessonsCount < 1) {
                return ApiResource::sendResponse("On-Demand courses must have at least one lesson with content.", null, 422);
            }

        }


        $course->status = 'pending';
        $course->save();

        return ApiResource::sendResponse("Course submitted for review successfully.", new CourseResource($course->load('sections')));
    }
}
