<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // محمي مسبقاً بالميدلواير الخاص بالطلاب
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'rating'    => 'required|integer|min:1|max:5',
            'review_text'    => 'nullable|string|max:1000',
        ];
    }
}
