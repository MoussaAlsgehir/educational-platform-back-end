<?php

namespace App\Http\Requests\CoursesRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 1. جلب آيدي الدرس الحالي من الـ Route إذا كنا في حالة التعديل (Update)
        $lessonId = $this->route('lesson');

        // 2. إذا كنا في حالة الإنشاء (POST)
        if ($this->isMethod('post')) {
            // نجيب الـ section_id من رابط الـ Route نفسه بأمان
            $sectionId = $this->route('section');

            return [
                'title' => [
                    'required',
                    'string',
                    'max:255',
                    // شرط الـ Unique: ممنوع تكرار الاسم بداخل هذا القسم تحديداً
                    Rule::unique('lessons')->where('section_id', $sectionId)
                ],
                'order'      => 'nullable|integer|min:1',
                'is_preview' => 'nullable|boolean',
            ];
        }

        // 3. إذا كنا في حالة التعديل (PUT/PATCH)
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // شرط الـ Unique للتعديل: يتأكد من عدم التكرار بنفس القسم ويستثني آيدي الدرس الحالي عشان ما يضرب مع نفسه
                Rule::unique('lessons')
                    ->where('section_id', $this->route('lesson') ? \App\Models\Lesson::find($lessonId)?->section_id : null)
                    ->ignore($lessonId)
            ],
            'order'      => 'sometimes|required|integer|min:1',
            'is_preview' => 'sometimes|required|boolean',
        ];
    }
}
