<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quizz;
use App\Helpers\ApiResource;
use App\Http\Requests\QuestionRequest\StoreQuestionRequest;
use App\Http\Requests\QuestionRequest\UpdateQuestionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->has('quizz_id')) {
            return ApiResource::sendResponse('The quizz_id field is required.', null, 422);
        }

        $questions = Question::with('answers')
            ->where('quizz_id', $request->quizz_id)
            ->whereHas('quizz.section.course', function ($q) {
                $q->where('teacher_id', Auth::id());
            })
            ->get();

        return ApiResource::sendResponse('Questions for the specified quiz retrieved successfully.', $questions, 200);
    }
    public function store(StoreQuestionRequest $request)
    {
        $validatedData = $request->validated();

        // التحقق من أن الكويز يتبع لكورس يملكه هذا المدرس الحالي
        $quiz = Quizz::where('id', $validatedData['quizz_id'])
            ->whereHas('section.course', function ($query) {
                $query->where('teacher_id', Auth::id());
            })->first();

        if (!$quiz) {
            return ApiResource::sendResponse('Access denied. You do not own this course.', null, 403);
        }

        DB::beginTransaction();
        try {
            $question = Question::create([
                'quizz_id'        => $validatedData['quizz_id'],
                'question_text'   => $validatedData['question_text'],
                'question_points' => $validatedData['question_points'],
            ]);

            $question->answers()->createMany($validatedData['answers']);

            DB::commit();

            return ApiResource::sendResponse(
                'Question and answers created successfully.',
                $question->load('answers'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResource::sendResponse(
                'An unexpected error occurred while saving the question.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show($id)
    {
        $question = Question::with('answers')->find($id);

        if (!$question) {
            return ApiResource::sendResponse('Question not found.', null, 200);
        }

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

        DB::beginTransaction();
        try {
            // تحديث بيانات السؤال الأساسية إن وجدت
            $question->update($request->only(['question_text', 'question_points', 'quizz_id']));

            // التعديل الذكي على الأجوبة دون الحذف العشوائي
            if ($request->has('answers')) {
                foreach ($validatedData['answers'] as $answerData) {
                    if (isset($answerData['id'])) {
                        // 1. إذا أرسل id الجواب، نقوم بتحديث هذا الجواب تحديداً
                        $question->answers()->where('id', $answerData['id'])->update([
                            'answer_text' => $answerData['answer_text'],
                            'is_correct'  => $answerData['is_correct'],
                        ]);
                    } else {
                        // 2. إذا لم يرسل id، نعتبره خياراً جديداً أضافه الأستاذ للسؤال
                        $question->answers()->create([
                            'answer_text' => $answerData['answer_text'],
                            'is_correct'  => $answerData['is_correct'],
                        ]);
                    }
                }
            }

            DB::commit();

            return ApiResource::sendResponse(
                'Question and answers updated successfully.',
                $question->load('answers'),
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResource::sendResponse(
                'An error occurred while updating the data.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
    public function destroy($id)
    {
        // التحقق من وجود السؤال وضمان ملكية المدرس للكورس التابع له قبل الحذف
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
