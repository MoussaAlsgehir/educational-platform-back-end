<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Helpers\ApiResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonProgressController extends Controller
{
    /**
     * Update or Create student progress for a specific lesson
     */
    public function updateProgress(Request $request, $lessonId)
    {
        $request->validate([
            'watched_seconds' => 'required|integer|min:0',
            'is_finished'     => 'sometimes|boolean',
        ]);

        $progress = LessonProgress::updateOrCreate(
            [
                'student_id' => Auth::id(),
                'lesson_id'  => $lessonId
            ],
            [
                'watched_seconds' => $request->watched_seconds,
                'is_completed'       => $request->is_finished ?? false,
            ]
        );

        return ApiResource::sendResponse('Progress updated successfully', $progress, 200);
    }

    /**
     * Get specific progress for a lesson
     */
    public function getProgress($lessonId)
    {
        $progress = LessonProgress::where('student_id', Auth::id())
            ->where('lesson_id', $lessonId)
            ->first();

        if (!$progress) {
            return ApiResource::sendResponse('No progress data found for this lesson', null, 200);
        }

        return ApiResource::sendResponse('Progress data retrieved successfully', $progress, 200);
    }

    /**
     * Manually mark a lesson as completed
     */
    public function markAsCompleted($lessonId)
    {
        $progress = LessonProgress::updateOrCreate(
            ['student_id' => Auth::id(), 'lesson_id' => $lessonId],
            ['is_completed' => true]
        );

        return ApiResource::sendResponse('Lesson marked as completed', $progress, 200);
    }

    /**
     * Reset progress for a lesson
     */
    public function resetProgress($lessonId)
    {
        $deleted = LessonProgress::where('student_id', Auth::id())
            ->where('lesson_id', $lessonId)
            ->delete();

        if (!$deleted) {
            return ApiResource::sendResponse('No progress to reset', null, 200);
        }

        return ApiResource::sendResponse('Progress has been reset', null, 200);
    }
}
