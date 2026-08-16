<?php

namespace App\Http\Requests\QuizzRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizzRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'passing_score' => 'sometimes|required|integer|min:0|max:100',
            'questions_count' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The quiz title cannot be empty.',
            'passing_score.integer' => 'The passing score must be a number.',
            'passing_score.min' => 'The passing score must be at least 0.',
            'passing_score.max' => 'The passing score cannot exceed 100.',
        ];
    }
}
