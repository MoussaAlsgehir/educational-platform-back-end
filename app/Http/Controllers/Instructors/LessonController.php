<?php

namespace App\Http\Controllers\Instructors;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CoursesRequest\LessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\LessonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    protected $lessonService;

    public function __construct(LessonService $lessonService)
    {
        $this->lessonService = $lessonService;
    }

    public function index(int $sectionId)
    {
        $section = Section::findOrFail($sectionId);


        Gate::authorize('view', $section->course);


        $lessons = $section->lessons()->orderBy('order', 'asc')->get();

        return ApiResource::sendResponse("Lessons retrieved successfully.", LessonResource::collection($lessons));
    }

    public function show(int $lessonId)
    {

        $lesson = Lesson::with(['section.course', 'contents' => function ($query) {
            $query->where(function ($q) {
                $q->where('type', '!=', 'video')->orWhere('status', 'ready');
            })->orderBy('order', 'asc');
        }])->findOrFail($lessonId);


        Gate::authorize('view', $lesson->course);

        return ApiResource::sendResponse("Lesson retrieved successfully.", new LessonResource($lesson));
    }

    /**
     * إنشاء درس جديد داخل قسم محدد
     */
    public function store(LessonRequest $request, int $sectionId)
    {
        $section = Section::findOrFail($sectionId);


        Gate::authorize('create', [Lesson::class, $section->course]);

        $lesson = $this->lessonService->createLesson($section, $request->validated());

        return ApiResource::sendResponse("Lesson created successfully.", new LessonResource($lesson));
    }

    public function update(LessonRequest $request, int $lessonId)
    {
        $lesson = Lesson::with('section.course')->findOrFail($lessonId);


        Gate::authorize('update', $lesson);

        $updatedLesson = $this->lessonService->updateLesson($lesson, $request->validated());

        return ApiResource::sendResponse("Lesson updated successfully.", new LessonResource($updatedLesson));
    }

    /**
     * حذف درس وسد الفجوات بالخلفية
     */
    public function destroy(int $lessonId)
    {
        $lesson = Lesson::with('section.course')->findOrFail($lessonId);

       
        Gate::authorize('delete', $lesson);

        $this->lessonService->deleteLesson($lesson);

        return ApiResource::sendResponse("Lesson deleted successfully.");
    }
}
