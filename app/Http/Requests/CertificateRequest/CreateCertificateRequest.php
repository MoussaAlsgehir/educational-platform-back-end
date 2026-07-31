<?php

namespace App\Http\Requests\CertificateRequest;

use Illuminate\Foundation\Http\FormRequest;

class CreateCertificateRequest extends FormRequest
{
    /**
     * تحديد سلطة إصدار الطلب (Authorization)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * الحصول على قواعد التحقق
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'معرف الطالب مطلوب',
            'student_id.integer' => 'معرف الطالب يجب أن يكون رقماً',
            'student_id.exists' => 'الطالب المحدد غير موجود',
            'course_id.required' => 'معرف الكورس مطلوب',
            'course_id.integer' => 'معرف الكورس يجب أن يكون رقماً',
            'course_id.exists' => 'الكورس المحدد غير موجود',
        ];
    }
}
