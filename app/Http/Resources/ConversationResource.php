<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];

        // 1. إذا محادثة كورس أو إعلان: رجع معلومات الكورس
        if (in_array($this->type, ['course_group', 'announcement']) && $this->course) {
            $data['course'] = [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ];
        }

        // 2. إذا شكوى (Complaint) وعندها Subject: رجع معلومات الشكوى
        if ($this->subtype === 'complaint' && $this->subject) {
            $data['subject'] = [
                // بنرجع اسم الموديل بس (مثلاً Message بدل App\Models\Message)
                'type' => Str::afterLast($this->subject_type, '\\'),
                'id' => $this->subject_id,
            ];
        }

        // 3. إذا محادثة خاصة (support/complaint/teacher_admin): رجع حالة الـ Admin Lock
        if (in_array($this->type, ['student_admin', 'teacher_admin'])) {
            $data['is_active'] = !is_null($this->active_by);
            $data['active_admin'] = $this->whenLoaded('activeAdmin', function () {
                return [
                    'id' => $this->activeAdmin->id,
                    'name' => $this->activeAdmin->name, // بيرجع الاسم الكامل عبر الـ Accessor
                ];
            });
        }

        return $data;
    }
}
