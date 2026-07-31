<?php

namespace App\Http\Controllers\Instructors;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Course;
use App\Http\Resources\SectionResource;
use App\Services\SectionService;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class SectionController extends Controller
{
    protected $sectionService;

    public function __construct(SectionService $sectionService)
    {
        $this->sectionService = $sectionService;
    }

    public function index(int $courseId)
    {
        $course = Course::findOrFail($courseId);
        $sections = $course->sections()->orderBy('order', 'asc')->with('lessons','attachments')->get();

        return ApiResource::sendResponse("Sections retrieved successfully.", SectionResource::collection($sections));
    }
    public function show(int $sectionId)
    {
        $section = Section::with('lessons','attachments')->findOrFail($sectionId);

        return ApiResource::sendResponse("Section retrieved successfully.", new SectionResource($section));
    }


      public function store(Request $request, int $courseId)
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('update', $course);

        // 2. منع إضافة قسم جديد بترتيب قبل القسم الأول (للكورسات الـ Live بعد النشر)
        $firstSection = $course->sections()->orderBy('order', 'asc')->first();
        if ($course->publish_type === 'live' && $course->is_published && $request->input('order', ($firstSection->order ?? 0) + 1) <= ($firstSection->order ?? 0)) {
            return ApiResource::sendResponse("Cannot add sections before the first section in a published Live course.", null, 422);
        }

        // 3. إجبار الكويز للقسم السابق (للكورسات الـ Live التسلسلية أثناء فترة الـ Active)
        if ($course->publish_type === 'live' && $course->status === 'active' && $course->navigation_type === 'sequential') {
            $lastSection = $course->sections()->orderBy('order', 'desc')->first();
            if ($lastSection && !$lastSection->quiz) {
                return ApiResource::sendResponse(
                    "Cannot add a new section: The previous section '{$lastSection->title}' must have a quiz before adding a new one.",
                    null, 422
                );
            }
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'nullable|integer',
        ]);

        $section = $this->sectionService->createSection($course, $data);

        return ApiResource::sendResponse("Section created successfully.", new SectionResource($section));
    }
    public function update(Request $request,int $sectionId)
    {
        $section = Section::findOrFail($sectionId);

        Gate::authorize('update', $section);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'order' => 'sometimes|integer|min:1',
        ]);

        $updatedSection = $this->sectionService->updateSection($section, $data);

        return ApiResource::sendResponse('Section Updated successfully',new SectionResource($section));
    }

    public function destroy(int $sectionId)
    {
        $section = Section::findOrFail($sectionId);
        Gate::authorize('update', $section->course);
        $section = Section::findOrFail($sectionId);




        $this->sectionService->deleteSection($section);

        return ApiResource::sendResponse('Section deleted successfully');
    }
}
