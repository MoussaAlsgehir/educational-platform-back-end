<?php

namespace App\Http\Requests\CoursesRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'title'                          => ['required', 'string', 'max:255', Rule::unique('courses')->where('teacher_id', Auth::id())],
                'description'                    => 'nullable|string',
                'course_type'                    => 'required|in:quiz_based,attendance_only',
                'publish_type'                   => 'required|in:live,on_demand',
                'navigation_type'                => 'sometimes|in:free,sequential',
                'price'                          => 'required|numeric|min:0|max:999999.99',

                'start_date'                     => 'required_if:publish_type,live|nullable|date|after_or_equal:today',
                'end_date'                       => 'required_if:publish_type,live|nullable|date|after_or_equal:start_date',

                'certificate_attendance_threshold' => 'required|integer|min:0|max:100',
                'category_ids'                   => 'required|array',
                'category_ids.*'                 => 'exists:categories,id',
                'cover_image'                    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'expected_sections_count'        => 'required|integer|min:0',
            ];
        }

        // قواعد للتعديل (PUT/PATCH) - باستخدام sometimes
        return [
            'title'                          => ['sometimes', 'required', 'string', 'max:255', Rule::unique('courses')->where('teacher_id', Auth::id())->ignore($this->route('course'))],
            'description'                    => 'sometimes|nullable|string',
            'course_type'                    => 'sometimes|required|in:quiz_based,attendance_only',
            'publish_type'                   => 'sometimes|required|in:live,on_demand',
            'navigation_type'                => 'sometimes|required|in:free,sequential',
            'price'                          => 'sometimes|required|numeric|min:0|max:999999.99',
            'start_date'                     => 'sometimes|required_if:publish_type,live|nullable|date|after_or_equal:today',
            'end_date'                       => 'sometimes|required_if:publish_type,live|nullable|date|after_or_equal:start_date',
            'certificate_attendance_threshold' => 'sometimes|required|integer|min:0|max:100',
            'category_ids'                   => 'sometimes|required|array',
            'category_ids.*'                 => 'exists:categories,id',
            'cover_image'                    => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'expected_sections_count'        => 'sometimes|required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required_if' => 'The start date is required for Live courses.',
            'end_date.required_if'   => 'The end date is required for Live courses.',
            'title.unique'           => 'You already have a course with this title.',
        ];
    }
}
