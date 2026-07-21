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
        //return response()->json;


            Gate::authorize('update', [Course::class, $course]);
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
