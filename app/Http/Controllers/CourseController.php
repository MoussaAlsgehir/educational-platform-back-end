<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResource;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Http\Requests\CoursesRequest\StoreCourseRequest;
use App\Services\CourseService;
use PHPUnit\TextUI\Help;

class CourseController extends Controller
{

    public function __construct( protected CourseService $courseService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $course = $this->courseService->createCourse($request->validated(),auth()->id());

        return ApiResource::sendResponse("Course created successfully.", $course,201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
