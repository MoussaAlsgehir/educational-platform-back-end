<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student' => [
                'id' => $this->student_id,
                'full_name' => $this->whenLoaded('student', function () {
                    return $this->student->first_name . ' ' . $this->student->last_name;
                }),
                'avatar_url' => $this->whenLoaded('student', function () {
                    return $this->student->avatar_url
                        ? asset('storage/' . $this->student->avatar_url)
                        : asset('storage/avatars/default-avatar.jpg');
                }),
            ],
            'lesson_id' => $this->lesson_id,
            'lesson_title' => $this->lesson->title,
            'note_text' => $this->note_text,
            'video_timestamp_seconds' => $this->video_timestamp_seconds,
            'is_private' => $this->is_private,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
