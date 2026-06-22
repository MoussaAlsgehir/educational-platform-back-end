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

class LessonController extends Controller
{
protected $lessonService;

    // حقن السيرفس المعقمة عبر الـ Constructor
    public function __construct(LessonService $lessonService)
    {
        $this->lessonService = $lessonService;
    }

    /**
     * جلب كل دروس قسم معين مرتبة تصاعدياً مع محتوياتها
     */
    public function index(int $sectionId)
    {
        $section = Section::findOrFail($sectionId);

        // Eager loading للمحتويات (contents) لتقليل ريكويستات قاعدة البيانات N+1
        $lessons = $section->lessons()->orderBy('order', 'asc')->with('contents')->paginate(9);

        return ApiResource::sendResponse("Lessons retrieved successfully.", LessonResource::collection($lessons));
    }

    public function show(int $lessonId)
    {
        $lesson = Lesson::with('contents')->findOrFail($lessonId);
        return ApiResource::sendResponse("Lesson retrieved successfully.", new LessonResource($lesson));
    }

    /**
     * إنشاء درس جديد داخل قسم محدد
     */
    public function store(LessonRequest $request,int  $sectionId)
    {
        $section = Section::findOrFail($sectionId);



        // الاستدعاء الساحر للسيرفس
        $lesson = $this->lessonService->createLesson($section, $request->validated());

        return ApiResource::sendResponse("Lesson created successfully.", new LessonResource($lesson));
    }

    /**
     * تعديل بيانات درس معين
     */
    public function update(LessonRequest $request,int $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);



        $updatedLesson = $this->lessonService->updateLesson($lesson, $request->validated());

        return ApiResource::sendResponse("Lesson updated successfully.", new LessonResource($updatedLesson));
    }

    /**
     * حذف درس وسد الفجوات بالخلفية
     */
    public function destroy(int $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);



        $this->lessonService->deleteLesson($lesson);

        return ApiResource::sendResponse("Lesson deleted successfully.");
    }
}

