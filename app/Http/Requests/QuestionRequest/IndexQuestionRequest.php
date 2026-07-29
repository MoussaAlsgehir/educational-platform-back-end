<?php
namespace App\Http\Requests\QuestionRequest;

use Illuminate\Foundation\Http\FormRequest;

class IndexQuestionRequest extends FormRequest
{
public function authorize(): bool
{
return true;
}

public function rules(): array
{
return [
'course_id' => ['required', 'integer', 'exists:courses,id'],
'quizz_id' => ['required', 'integer', 'exists:quizzes,id'],
];
}
}
