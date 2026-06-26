<?php

namespace App\Http\Controllers\Students;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;

class StudentCourseController extends Controller
{
    public function index()
    {
         $courses = Course::with(['categories'])->whereNotIn('status', ['rejected','pending'])->paginate(9);

         return ApiResource::sendResponse("Courses retrieved successfully.", CourseResource::collection($courses));
    }

    public function show(int $id)
    {
        $course = Course::with(['categories'])->findOrFail($id);

        return ApiResource::sendResponse("Course retrieved successfully.", new CourseResource($course));
    }

    public function showByCategory(int $category_id)
    {
        $courses = Course::whereHas('categories', function ($query) use ($category_id) {
            $query->where('categories.id', $category_id);
        })->with(['categories'])->paginate(9);

        return ApiResource::sendResponse("Courses retrieved successfully.", CourseResource::collection($courses));
    }
}
