<?php

namespace App\Http\Controllers\Instructors;

use App\Helpers\ApiResource ;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonContent;
use App\Http\Requests\CoursesRequest\LessonContentRequest;
use App\Http\Resources\LessonContentResource;
use App\Services\LessonContentService;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class LessonContentController extends Controller
{
    protected  $contentService;

    public function __construct(LessonContentService $contentService)
    {
        $this->contentService = $contentService;
    }

    /**
     * إنشاء محتوى جديد (Text أو PDF) داخل درس معين
     */
    public function store(LessonContentRequest $request, int $lessonId)
    {
        $lesson = Lesson::with('section.course')->findOrFail($lessonId);

        // حماية الأمن: التأكد أن المدرس يملك الكورس الذي يتبع له هذا الدرس
        Gate::authorize('update',$lesson->section->course);


        $content = $this->contentService->createContent(
            $lesson,
            $request->validated(),
            $request->file('file')
        );

        return ApiResource::sendResponse("Lesson content created successfully.",new LessonContentResource($content), 201);
    }

    /**
     * تعديل محتوى معين
     */
    public function update(LessonContentRequest $request,  int $contentId)
    {
        $content = LessonContent::findOrFail($contentId);

        Gate::authorize('update',$content->lesson);
        $updatedContent = $this->contentService->updateContent(
            $content,
            $request->validated(),
            $request->file('file')
        );

        return ApiResource::sendResponse("Lesson content updated successfully.", new LessonContentResource($updatedContent));
    }

    /**
     * حذف محتوى وسد الفجوات
     */
    public function destroy( int $contentId)
    {
        $content = LessonContent::findOrFail($contentId);

        Gate::authorize('update',$content->lesson);

        $this->contentService->deleteContent($content);

        return ApiResource::sendResponse("Lesson content deleted successfully.");
    }
}
