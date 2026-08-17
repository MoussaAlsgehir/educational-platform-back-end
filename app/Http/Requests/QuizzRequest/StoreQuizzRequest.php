<?php

namespace App\Http\Requests\QuizzRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizzRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'passing_score' => 'required|integer|min:0|max:100',
            'questions_count' => 'sometimes|nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The quiz title is required.',
            'passing_score.required' => 'The passing score is required.',
            'passing_score.min' => 'The passing score must be at least 0.',
            'passing_score.max' => 'The passing score cannot exceed 100.',
        ];
    }
}
