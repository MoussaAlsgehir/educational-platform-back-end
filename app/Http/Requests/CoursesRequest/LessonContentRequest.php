<?php

namespace App\Http\Requests\CoursesRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contentId = $this->route('content');

        // 1. في حالة الإنشاء (Store)
        if ($this->isMethod('post')) {
            return [
                'type'       => 'required|in:pdf,text_article,quiz',
                'title'      => 'nullable|string|max:255',
                'order'      => 'nullable|integer|min:1',
                // إذا كان المحتوى نصي، الـ text_value مطلوب
                'text_value' => 'required_if:type,text_article|text|nullable',
                // إذا كان PDF، نطلب الملف مباشرة بالـ Store
                'file'       => 'required_if:type,pdf,file|mimes:pdf|max:20480|nullable', // حد أقصى 20 ميجا للـ PDF
            ];
        }

        // 2. في حالة التعديل (Update)
        return [
            'title'      => 'sometimes|nullable|string|max:255',
            'order'      => 'sometimes|required|integer|min:1',
            'text_value' => 'sometimes|required_if:type,text_article|text',
            'file'       => 'nullable|file|mimes:pdf|max:20480',
        ];
    }
}
