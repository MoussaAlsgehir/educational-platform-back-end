<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Models\StudentAttempt;
use App\Services\QuizService;
use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizAttemptController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    // 1. عرض سجل المحاولات الخاص بالطالب فقط
    public function index()
    {
        $attempts = StudentAttempt::where('student_id', Auth::id())
            ->latest() // مرتبة من الأحدث للأقدم
            ->get();

        return ApiResource::sendResponse('Quiz attempts history retrieved successfully!', $attempts);
    }

    // 4. عرض جميع محاولات الطالب لاختبار معين
    public function getAttemptsByQuiz($quizId)
    {
        $attempts = StudentAttempt::where('student_id', Auth::id())
            ->where('quiz_id', $quizId)
            ->latest()
            ->get();

        if ($attempts->isEmpty()) {
            return ApiResource::sendResponse('No attempts found for this quiz.', null, 200);
        }

        return ApiResource::sendResponse('Quiz attempts retrieved successfully!', $attempts,200);
    }
    // 2. تسجيل محاولة جديدة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzs,id',
            'score'   => 'required|numeric|min:0|max:100',
        ]);

        $attempt = $this->quizService->recordAttempt(
            Auth::id(),
            $validated['quiz_id'],
            $validated['score']
        );

        return ApiResource::sendResponse('Quiz attempt recorded successfully!', $attempt, 201);
    }

    // 3. عرض نتيجة محاولة معينة (مع حماية مشددة)
    public function show($id)
    {
        // استخدام شرط student_id يضمن أمن البيانات بنسبة 100%
        $attempt = StudentAttempt::where('id', $id)
            ->where('student_id', Auth::id())
            ->first();

        // في حال حاول الطالب الوصول لـ ID لا يخصه، سيرجع 404
        if (!$attempt) {
            return ApiResource::sendResponse('Attempt not found or unauthorized access!', null, 403);
        }

        return ApiResource::sendResponse('Attempt details retrieved successfully!', $attempt);
    }


public function destroy($id)
    {

        $attempt = StudentAttempt::where('id', $id)
            ->first();

        if (!$attempt) {
            return ApiResource::sendResponse('Attempt not found ', null, 200);
        }

        $attempt->delete();

        return ApiResource::sendResponse('Attempt deleted successfully!', null, 200);

    }
}
