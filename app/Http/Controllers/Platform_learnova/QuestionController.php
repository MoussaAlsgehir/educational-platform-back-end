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
use App\Models\Answer;
use App\Models\Section;
use Illuminate\Http\Request;
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


        $quiz = Quizz::find($quizzId);


        $query = Question::with('answers')->where('quizz_id', $quizzId);

        // 2.  منطق بنك الأسئلة
        if (!$isTeacher && $quiz) {
            $dbCount = $query->count(); // عدد الأسئلة الكلي بالبنك

            // تحديد العدد المطلوب (إذا فارغ، نعتمد 5 كـ Default)
            $targetCount = $quiz->questions_count ? $quiz->questions_count : 5;

            // العدد النهائي هو الأقل بين (المطلوب) و (الموجود بالبنك)
            $finalCount = min($targetCount, $dbCount);

         $questions = $query->inRandomOrder()
                ->take($finalCount)
                ->get()
                ->map(function ($question) {
                    $shuffledAnswers = $question->answers->shuffle();
                    $question->setRelation('answers', $shuffledAnswers);
                    return $question;
                });
        } else {
            // المدرس: نرجع كل الأسئلة بترتيبها العادي ليراجعها
            $questions = $query->get();
        }

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

        /**
     * توليد أسئلة بالذكاء الاصطناعي وحفظها
     */
    public function generateAiQuestions(Request $request, $sectionId, $quizId)
    {
        $request->validate([
            'num_questions' => 'required|integer|min:1|max:20',
            'default_points' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'multiple_ratio' => 'nullable|integer|in:0,1,2,3',
            'true_false_ratio' => 'nullable|integer|in:0,1,2,3',
            'language' => 'nullable|in:en,ar',
            'difficulty' => 'nullable|integer|in:0,1,2,3'
        ]);

        $section = Section::with(['lessons.contents', 'course'])->findOrFail($sectionId);
        $quiz = Quizz::findOrFail($quizId);

        if (Auth::user()->hasRole('instructor') && $section->course->teacher_id !== auth()->id()) {
            return ApiResource::sendResponse("Access denied. You do not own this course.", null, 403);
        }

        $config = [
            'num_questions' => $request->num_questions,
            'default_points' => $request->default_points ?? 2,
            'notes' => $request->notes,
            'multiple_ratio' => $request->multiple_ratio ?? 0,
            'true_false_ratio' => $request->true_false_ratio ?? 0,
            'language' => $request->language ?? 'ar',
            'difficulty' => $request->difficulty ?? 0
        ];

        try {
            $aiService = new \App\Services\AiService();
            $generatedQuestions = $aiService->generateQuizQuestions($section, $config);

            $savedQuestions = [];
            DB::transaction(function () use ($generatedQuestions, $quiz, $config, &$savedQuestions) {
                foreach ($generatedQuestions as $qData) {
                    $question = Question::create([
                        'quizz_id' => $quiz->id,
                        'question_text' => $qData['question'],
                        'question_points' => $config['default_points']
                    ]);

                    foreach ($qData['options'] as $option) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => $option,
                            'is_correct' => ($option === $qData['correct_answer'])
                        ]);
                    }
                    $savedQuestions[] = $question->load('answers');
                }
            });

            return ApiResource::sendResponse("AI questions generated successfully.", $savedQuestions, 201);

        } catch (\Exception $e) {
            \Log::error('AI Quiz Error: ' . $e->getMessage());
            return ApiResource::sendResponse("Failed to generate questions: " . $e->getMessage(), null, 500);
        }
    }
}
