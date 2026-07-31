<?php

namespace App\Http\Controllers\Students;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFilterRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseStatusService;
use Auth;

class StudentCourseController extends Controller
{

    public function index(CourseFilterRequest $request)
    {

        $query = Course::query()->with(['categories' , 'attachments']);


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

     public function show(int $id){
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
    ])->available()->findOrFail($id);

    CourseStatusService::refresh($course);

    return ApiResource::sendResponse("Course retrieved successfully.", new CourseResource($course));
     }

}
