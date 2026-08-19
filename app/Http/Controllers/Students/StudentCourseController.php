<?php

namespace App\Http\Controllers\Students;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFilterRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\CourseStatusService;
use Auth;
use Illuminate\Http\Request;

class StudentCourseController extends Controller
{

    public function index(CourseFilterRequest $request)
    {

        $query = Course::query()->with(['categories' , 'attachments'])
         ->withAvg('reviews', 'rating')
         ->withCount('reviews')->available();


        $query->search($request->search)
              ->categoryFilter($request->category_ids)
              ->priceFilter($request->min_price, $request->max_price)
              ->statusFilter($request->status);


        switch ($request->sort) {
                                                                                                                                                                                                                                                                                                                                                                                                                case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $courses = $query->paginate(9);

        return ApiResource::sendResponse("Courses retrieved successfully.", [
            'courses' => CourseResource::collection($courses->items()),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page'    => $courses->lastPage(),
                'per_page'     => $courses->perPage(),
                'total'        => $courses->total(),
                'has_more'     => $courses->hasMorePages(),
            ]

        ]);
    }

        public function myCourses(Request $request)
    {
        $user = $request->user('sanctum');


        $courses = $user->courses()
            ->with(['teacher', 'categories'])
             ->withAvg('reviews', 'rating')
             ->withCount('reviews','students')

            ->paginate(9);

        return ApiResource::sendResponse("My courses retrieved successfully.", [
            'courses' => CourseResource::collection($courses->items()),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page'    => $courses->lastPage(),
                'total'        => $courses->total(),
                'has_more'     => $courses->hasMorePages(),
            ]
        ]);
    }




     public function show(Request $request,int $id){
         $course = Course::Where('is_published',true)->with([
    'teacher',
    'categories',
    'attachments' => function($q) { $q->whereNull('section_id'); },
    'sections.lessons.contents',
    'sections.attachments',
    'sections.lessons.studentProgress' => function ($query) {
        $query->where('student_id', Auth::id());
        },
    'sections.quiz',
    'sections.quiz.studentAttempts' => function ($q) { $q->where('student_id',Auth::id()); }
    ])
     ->withAvg('reviews', 'rating')
    ->withCount('reviews','students')

    ->available()->findOrFail($id);


     if ($course->status === 'hidden') {
            $user = $request->user('sanctum');

            $isEnrolled = $user ? $course->students()->where('student_id', $user->id)->exists() : false;
            $isOwnerOrAdmin = $user ? ($user->isAdmin() || $course->teacher_id === $user->id) : false;

            if (!$isEnrolled && !$isOwnerOrAdmin) {
                return ApiResource::sendResponse("This course is currently not available.", null, 404);
            }
        }



    CourseStatusService::refresh($course);

    return ApiResource::sendResponse("Course retrieved successfully.", new CourseResource($course));
     }


         /**
     * جلب أعلى 5 كورسات تقييماً (للواجهة الرئيسية)
     */
    public function topRated()
    {

        $courses = Course::available()
            ->where('is_published', true)
            ->withAvg('reviews', 'rating')
            ->withCount('students')
            ->orderByDesc('reviews_avg_rating')
            ->take(5)
            ->get();

        return ApiResource::sendResponse("Top rated courses retrieved.", CourseResource::collection($courses));
    }

    /**
     * جلب 5 كورسات مقترحة عشوائياً (للواجهة الرئيسية)
     */
    public function suggested()
    {
        $courses = Course::
         withAvg('reviews', 'rating')
            ->withCount('reviews','students')
            ->available()
            ->where('is_published', true)
            ->inRandomOrder()
            ->take(5)
            ->get();

        return ApiResource::sendResponse("Suggested courses retrieved.", CourseResource::collection($courses));
    }

        public function continueWatching(Request $request )
    {
        $user = Auth::user();
        $language=$request->language ?? 'ar'; 

        $progress =LessonProgress::where('student_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->with('lesson.section.course')
            ->first();

        if (!$progress) {
            return ApiResource::sendResponse("No progress yet.", null, 200);
        }

        $course = $progress->lesson->section->course;
        $lesson = $progress->lesson;

        $aiInsight = null;


        try {
            $aiService = new \App\Services\AiService();
            $aiInsight = $aiService->generateLessonInsight($course, $lesson, $language);
        } catch (\Exception $e) {
            \Log::error('AI Insight Error: ' . $e->getMessage());
        }

        return ApiResource::sendResponse("Continue watching.", [
            'course_id' => $course->id,
            'course_title' => $course->title,
            'lesson_id' => $lesson->id,
            'lesson_title' => $lesson->title,
            'watched_seconds' => $progress->watched_seconds,
            'ai_insight' => $aiInsight
        ]);
    }

}
