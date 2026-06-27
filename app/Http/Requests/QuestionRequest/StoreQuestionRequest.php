<?php

namespace App\Http\Requests\QuestionRequest;

use App\Helpers\ApiResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quizz_id'              => 'required|exists:quizzs,id',
            'question_text'         => 'required|string',
            'question_points'       => 'required|numeric|min:0',

            // مصفوفة الأجوبة إلزامية عند الإنشاء
            'answers'               => 'required|array|min:2',
            'answers.*.answer_text' => 'required|string',
            'answers.*.is_correct'  => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'quizz_id.required'         => 'The quiz ID is required.',
            'quizz_id.exists'           => 'The selected quiz does not exist.',
            'question_text.required'    => 'The question text field is required.',
            'question_points.required'  => 'The question points field is required.',
            'question_points.numeric'   => 'The question points must be a number.',
            'question_points.min'       => 'The question points cannot be less than 0.',
            'answers.required'          => 'The answers array is required.',
            'answers.array'             => 'The answers must be an array.',
            'answers.min'               => 'You must provide at least 2 answers.',
            'answers.*.answer_text.required' => 'The answer text is required for each provided answer.',
            'answers.*.is_correct.required'  => 'The correctness status (is_correct) is required for each answer.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = ApiResource::sendResponse(
            'Validation failed.',
            $validator->errors(),
            422
        );

        throw new HttpResponseException($response);
    }
}
