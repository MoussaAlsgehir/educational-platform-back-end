<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAccessible = false;

        if ($this->lesson && $this->lesson->section) {
            $user = $request->user('sanctum');
            $course = $this->lesson->section->course;

            // 1. إذا الدرس مجاني (بريفيو)
            if ($this->lesson->is_preview) {
                $isAccessible = true;
            }
            // 2. إذا المستخدم أدمن أو المدرس صاحب الكورس
            elseif ($user && ($user->isAdmin() || $course->teacher_id === $user->id)) {
                $isAccessible = true;
            }
            // 3. إذا الطالب مشترك بالكورس
            elseif ($user && $course->students()->where('student_id', $user->id)->exists()) {
                $isAccessible = true;
            }
        }

        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'order' => $this->order,
            'status' => $this->status,
        ];

        // النص يظهر للجميع
        if ($this->type === 'text_article') {
            $data['text_value'] = $this->text_value;
        }

        // الفيديو والـ PDF يظهروا فقط إذا كان $isAccessible = true
        if ($isAccessible) {
            if ($this->type === 'pdf') {
                $data['download_url'] = $this->storage_key
                    ? rtrim(env('CLOUDFLARE_WORKER_URL'), '/') . '/' . $this->storage_key
                    : null;
            } elseif ($this->type === 'video' && $this->status === 'ready') {
                $data['duration'] = $this->duration;
                $data['playback_url'] = rtrim(env('CLOUDFLARE_WORKER_URL'), '/') . '/' . $this->storage_key;
            }
        }

        return $data;
    }
}
