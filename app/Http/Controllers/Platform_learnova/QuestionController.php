<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quizz;
use App\Models\Course;
use App\Helpers\ApiResource;
use App\Http\Requests\QuestionRequest\IndexQuestionRequest;
use App\Http\Requests\QuestionRequest\StoreQuestionRequest;
use App\Http\Requests\QuestionRequest\UpdateQuestionRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(IndexQuestionRequest $request)
    {
        $user = Auth::user();
        $courseId = $request->validated('course_id');
        $quizzId = $request->validated('quizz_id');

        $course = Course::select('id', 'teacher_id')->find($courseId);

        if (!$course) {
            return ApiResource::sendResponse('Course not found.', null, 404);
        }

        // 1. التحقق من الصلاحيات (المدرس أو الطالب المسجل)
        $isTeacher = ($course->teacher_id === $user->id);
        $isEnrolledStudent = $user->courses()->where('course_id', $courseId)->exists();

        if (!$isTeacher && !$isEnrolledStudent) {
            return ApiResource::sendResponse('Access denied. You are not enrolled or owning this course.', null, 403);
        }

        // 2. جلب الأسئلة
        $questions = Question::with('answers')
            ->where('quizz_id', $quizzId)
            ->get();

        return ApiResource::sendResponse('Questions retrieved successfully.', $questions, 200);
    }

    public function store(StoreQuestionRequest $request)
    {
        $validatedData = $request->validated();

        // التحقق من أن الكويز يتبع لكورس يملكه هذا المدرس
        $quiz = Quizz::where('id', $validatedData['quizz_id'])
            ->whereHas('section.course', function ($query) {
                $query->where('teacher_id', Auth::id());
            })->first();

        if (!$quiz) {
            return ApiResource::sendResponse('Access denied. You do not own this course.', null, 403);
        }

        return DB::transaction(function () use ($validatedData) {
            $question = Question::create([
                'quizz_id'        => $validatedData['quizz_id'],
                'question_text'   => $validatedData['question_text'],
                'question_points' => $validatedData['question_points'],
            ]);

            $question->answers()->createMany($validatedData['answers']);

            return ApiResource::sendResponse(
                'Question and answers created successfully.',
                $question->load('answers'),
                201
            );
        });
    }

    public function show($id)
    {
        $user = Auth::user();

        // جلب السؤال مع الكورس المرتبط به
        $question = Question::with(['answers', 'quizz.section.course'])->find($id);

        if (!$question) {
            return ApiResource::sendResponse('Question not found.', null, 404);
        }

        $course = $question->quizz->section->course ?? null;

        if (!$course) {
            return ApiResource::sendResponse('Associated course not found.', null, 404);
        }

        // التحقق من الصلاحيات
        $isTeacher = ($course->teacher_id === $user->id);
        $isEnrolledStudent = $user->courses()->where('course_id', $course->id)->exists();

        if (!$isTeacher && !$isEnrolledStudent) {
            return ApiResource::sendResponse('Access denied.', null, 403);
        }

        // تنظيف الاستجابة بفك العلاقة المؤقتة
        $question->unsetRelation('quizz');

        return ApiResource::sendResponse('Question retrieved successfully.', $question, 200);
    }

    public function update(UpdateQuestionRequest $request, $id)
    {
        $question = Question::where('id', $id)
            ->whereHas('quizz.section.course', function ($query) {
                $query->where('teacher_id', Auth::id());
            })->first();

        if (!$question) {
            return ApiResource::sendResponse('Question not found or access denied.', null, 403);
        }

        $validatedData = $request->validated();

        return DB::transaction(function () use ($question, $request, $validatedData) {
            // تحديث بيانات السؤال الأساسية
            $question->update($request->only(['question_text', 'question_points', 'quizz_id']));

            // مزامنة الأجوبة الذكية (تحديث، إضافة، وحذف المستغنى عنه)
            if (isset($validatedData['answers'])) {
                $submittedIds = collect($validatedData['answers'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // حذف الأجوبة التي أزالها المستخدم من الشاشة
                $question->answers()->whereNotIn('id', $submittedIds)->delete();

                // تحديث أو إضافة الخيارات بأسلوب أنيق
                foreach ($validatedData['answers'] as $answerData) {
                    $question->answers()->updateOrCreate(
                        ['id' => $answerData['id'] ?? null],
                        [
                            'answer_text' => $answerData['answer_text'],
                            'is_correct'  => $answerData['is_correct'],
                        ]
                    );
                }
            }

            return ApiResource::sendResponse(
                'Question and answers updated successfully.',
                $question->load('answers'),
                200
            );
        });
    }

    public function destroy($id)
    {
        $question = Question::where('id', $id)
            ->whereHas('quizz.section.course', function ($query) {
                $query->where('teacher_id', Auth::id());
            })->first();

        if (!$question) {
            return ApiResource::sendResponse('Question not found or access denied.', null, 403);
        }

        $question->delete();

        return ApiResource::sendResponse('Question and its answers deleted successfully.', null, 200);
    }
}
