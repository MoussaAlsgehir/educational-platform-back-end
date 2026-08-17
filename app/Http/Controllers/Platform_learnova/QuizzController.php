<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Models\Quizz;
use App\Models\Section;
use App\Http\Requests\QuizzRequest\QuizzRequest;
use App\Http\Requests\QuizzRequest\StoreQuizzRequest;
use App\Http\Requests\QuizzRequest\UpdateQuizzRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class QuizzController extends Controller
{
    /**
     * 1. Display the quiz belonging to a specific section.
     */
    public function index($sectionId): JsonResponse
    {
        // جلب الكويز الخاص بهذا السكشن تحديداً تماشياً مع الـ Route Param
        $quizz = Quizz::where('section_id', $sectionId)->first();

        if (!$quizz) {
            return ApiResource::sendResponse("No quiz found for this section.", null, 404);
        }

        return ApiResource::sendResponse("Quiz retrieved successfully.", $quizz, 200);
    }

    /**
     * 2. Store a newly created quiz in a specific section.
     */
    public function store(StoreQuizzRequest $request, $sectionId): JsonResponse
    {
        $section = Section::with('course')->findOrFail($sectionId);

        //هنا الاضافة فقط لمنع الكويزات اذا كان الكورس من النوع المشاهدة فقط
     if ($section->course->course_type === 'attendance_only') {
            return ApiResource::sendResponse("Cannot add quizzes to an attendance-only course.", null, 422);
        }

        // التحقق من الملكية: هل المدرس الحالي هو صاحب الكورس؟
        if (Auth::user()->hasRole('instructor') && $section->course->teacher_id !== auth()->id()) {
            return ApiResource::sendResponse("You do not have permission to add a quiz to this course.", null, 403);
        }

          //  منع وجود أكثر من كويز لنفس القسم
        $existingQuiz = Quizz::where('section_id', $sectionId)->exists();
        if ($existingQuiz) {
            return ApiResource::sendResponse("This section already has a quiz. You can edit it instead.", null, 422);
        }

        // دمج الـ section_id تلقائياً من الرابط
        $data = array_merge($request->validated(), [
            'section_id' => $sectionId
        ]);

        $quizz = Quizz::create($data);

        return ApiResource::sendResponse("Quiz created successfully.", $quizz, 201);
    }

    /**
     * 3. Display the specified quiz.
     */
    public function show(Quizz $quizz): JsonResponse
    {
        return ApiResource::sendResponse("Quiz details retrieved successfully.", $quizz, 200);
    }

    /**
     * 4. Update the specified quiz in storage.
     */
    /**
     * 4. Update the specified quiz in storage.
     */
    public function update(UpdateQuizzRequest $request, Quizz $quizz): JsonResponse
    {
        // 1. جلب السكشن والكورس للتحقق من الملكية
        $section = Section::with('course')->findOrFail($quizz->section_id);

        if (Auth::user()->hasRole('instructor') && $section->course->teacher_id !== Auth::id()) {
            return ApiResource::sendResponse("You do not have permission to update this quiz.", null, 403);
        }

        // 2. عمل ملء (Fill) للبيانات القادمة من الـ Request دون حفظها فوراً
        $quizz->fill($request->validated());

        // 3. الفحص قبل الحفظ: هل تم تعديل أي حقل？
        if (!$quizz->isDirty()) {
            return ApiResource::sendResponse("No changes were made.", $quizz, 200);
        }

        // 4. إذا كانت البيانات متسخة (Dirty) أي تغيرت، نقوم بالحفظ
        $quizz->save();

        return ApiResource::sendResponse("Quiz updated successfully.", $quizz, 200);
    }
    /**
     * 5. Remove the specified quiz from storage.
     */
    public function destroy(Quizz $quizz): JsonResponse
    {
        // جلب السكشن والكورس للتحقق من الملكية
        $section = Section::with('course')->findOrFail($quizz->section_id);

        if (Auth::user()->hasRole('instructor') && $section->course->teacher_id !== Auth::user()->id) {
            return ApiResource::sendResponse("You do not have permission to delete this quiz.", null, 403);
        }

        $quizz->delete();

        return ApiResource::sendResponse("Quiz deleted successfully.", null, 200);
    }

    }
