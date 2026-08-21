<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAccessible = false;

        $user = $request->user('sanctum');

        if ($user) {
            $course = $this->section->course;

            // فحص إذا كان أدمن أو المدرس صاحب الكورس
            if ($user->isAdmin() || $course->teacher_id === $user->id) {
                $isAccessible = true;
            }
            // فحص إذا كان طالب مشترك
            elseif ($course->students()->where('student_id', $user->id)->exists()) {
                $isAccessible = $course->status!=='upcoming'&& $course->status!=='upcoming';
            }
        }

        $isAccessible = $isAccessible || $this->is_preview;
        $studentProgress = $this->studentProgress()->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'order' => $this->order,
            'is_preview' => $this->is_preview,
            'is_locked' => !$isAccessible,
              'progress' => [
                'watched_seconds' => $studentProgress ? $studentProgress->watched_seconds : 0,
                'is_completed' => $studentProgress ? $studentProgress->is_completed : false,
            ],

            'contents' => LessonContentResource::collection($this->whenLoaded('contents')),
        ];
    }
}
