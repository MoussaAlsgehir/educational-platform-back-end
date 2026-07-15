<?php

namespace App\Http\Requests\QuestionRequest;

use App\Helpers\ApiResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quizz_id'              => 'sometimes|required|exists:quizzs,id',
            'question_text'         => 'sometimes|required|string',
            'question_points'       => 'sometimes|required|numeric|min:0',

            'answers'               => 'sometimes|required|array',
            // نتحقق من أن الـ id المرسل ينتمي فعلياً لجدول الأجوبة (اختياري)
            'answers.*.id'          => 'sometimes|required|exists:answers,id',
            'answers.*.answer_text' => 'required_with:answers|string',
            'answers.*.is_correct'  => 'required_with:answers|boolean',
        ];
    }
    public function messages(): array
    {
        return [
            'quizz_id.exists'           => 'The selected quiz does not exist.',
            'question_points.numeric'   => 'The question points must be a number.',
            'question_points.min'       => 'The question points cannot be less than 0.',
            'answers.array'             => 'The answers must be an array.',
            'answers.min'               => 'You must provide at least 2 answers if updating answers.',
            'answers.*.answer_text.required_with' => 'The answer text is required for each provided answer.',
            'answers.*.is_correct.required_with'  => 'The correctness status (is_correct) is required for each answer.',
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
