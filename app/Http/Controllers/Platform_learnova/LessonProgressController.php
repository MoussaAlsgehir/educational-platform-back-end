<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Models\Lesson;
use App\Helpers\ApiResource;
use App\Services\CertificateAutoGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LessonProgressController extends Controller
{
  private CertificateAutoGeneratorService $certificateGenerator;

  public function __construct(CertificateAutoGeneratorService $certificateGenerator)
  {
    $this->certificateGenerator = $certificateGenerator;
  }

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

    // التحقق من اكتمال جميع الدروس وإنشاء الشهادة تلقائياً
    $this->checkAndGenerateCertificate($lessonId);

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

  $lesson_exists = Lesson::where('id', $lessonId)->exists();
  if (!$lesson_exists) {
    return ApiResource::sendResponse('Lesson not found', null, 200);
  }

  $progress = LessonProgress::updateOrCreate(
    ['student_id' => Auth::id(), 'lesson_id' => $lessonId],
    ['is_completed' => true]
  );

    // التحقق من اكتمال جميع الدروس وإنشاء الشهادة تلقائياً
    $this->checkAndGenerateCertificate($lessonId);

    return ApiResource::sendResponse('Lesson marked as completed', $progress, 200);
  }

  /**
   * Reset progress for a lesson
   */
  

  /**
   * التحقق من اكتمال جميع دروس الكورس وإنشاء الشهادة تلقائياً
   *
   * @param int $lessonId
   * @return void
   */
  private function checkAndGenerateCertificate(int $lessonId): void
  {
    try {
      // الحصول على الدرس والقسم والكورس
      $lesson = Lesson::find($lessonId);
      if (!$lesson || !$lesson->section) {
        return;
      }

      $courseId = $lesson->section->course_id;
      $studentId = Auth::id();

      // استدعاء الخدمة للتحقق والإنشاء التلقائي
      $this->certificateGenerator->generateCertificateIfAllLessonsCompleted($studentId, $courseId);
    } catch (\Exception $e) {
      Log::error("خطأ في إنشاء الشهادة تلقائياً: " . $e->getMessage());
    }
  }
}
