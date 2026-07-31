<?php

namespace App\Http\Resources;

use App\Models\Section;
use App\Models\StudentAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isEnrolled = false;
        $isSequentiallyLocked = false;
        $user = $request->user('sanctum');
        if ($user) {
            $course = $this->course;
            if ($user->isAdmin() || $course->teacher_id === $user->id) {
                $isEnrolled = true;
            } else {
                $isEnrolled = $course->students()->where('student_id', $user->id)->exists();
            }
        }

           if ($this->course->navigation_type === 'sequential' && $user && !$user->isAdmin()) {

            $previousSection = Section::where('course_id', $this->course_id)
                ->where('order', '<', $this->order)
                ->orderBy('order', 'desc')
                ->first();

            if ($previousSection) {

                $prevQuiz = $previousSection->quiz;

                if ($prevQuiz) {
                    $passedQuiz = StudentAttempt::where('student_id', $user->id)
                        ->where('quiz_id', $prevQuiz->id)
                        ->where('is_passed', true)
                        ->exists();

                    if (!$passedQuiz) {
                        $isSequentiallyLocked = true;
                    }
                }
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'order' => $this->order,
            'is_locked' =>  $isSequentiallyLocked,

              // 1. إضافة الكويز للقسم
            'quiz' => $this->whenLoaded('quiz', function () use ($request) {
                if (!$this->quiz) return null;


                $isPassed = $this->quiz->studentAttempts->contains('is_passed', true);

                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'passing_score' => $this->quiz->passing_score,
                    'is_passed' => $isPassed,
                ];
            }),

            // 2. حماية مرفقات القسم
            'attachments' => $this->whenLoaded('attachments', function () use ($isEnrolled) {
                return $this->attachments->map(function ($attachment) use ($isEnrolled) {
                    $url = null;

                    if ($isEnrolled) {
                        $url = $attachment->file_url;
                        if ($attachment->type !== 'link' && $url && !filter_var($url, FILTER_VALIDATE_URL)) {
                            $url = rtrim(env('CLOUDFLARE_WORKER_URL'), '/') . '/' . $url;
                        }
                    }

                    return [
                        'id' => $attachment->id,
                        'title' => $attachment->title,
                        'type' => $attachment->type,
                        'is_locked' =>!$isEnrolled,
                        'url' => $url,
                    ];
                });
            }),

            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
