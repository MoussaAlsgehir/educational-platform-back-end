<?php

namespace App\Http\Controllers\Students;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseFilterRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;

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

    public function show(int $id)
    {
        $course = Course::with(['categories' , 'attachments'])->available()->findOrFail($id);
        return ApiResource::sendResponse("Course retrieved successfully.", new CourseResource($course));
    }
}
