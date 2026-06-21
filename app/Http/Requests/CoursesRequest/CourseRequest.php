<?php

namespace App\Http\Requests\CoursesRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->method() === 'post') {
         return [
            'title' => 'required|string|max:255|unique:courses,title,NULL,id,teacher_id,' . Auth::id(),
            'description' => 'nullable|string',
            'course_type' => 'sometimes|in:quiz_based,attendance_only',
            'price' => 'required|numeric|min:0|max:999999.99',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'certificate_attendance_threshold' => 'sometimes|integer|min:0|max:100',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
        }
        return [
            'title' => 'sometimes|string|max:255|unique:courses,title,' . $this->route('course') . ',id,teacher_id,' . Auth::id(),
            'description' => 'nullable|string',
            'course_type' => 'sometimes|in:quiz_based,attendance_only',
            'price' => 'sometimes|numeric|min:0|max:999999.99',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'certificate_attendance_threshold' => 'sometimes|integer|min:0|max:100',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

    }
}
