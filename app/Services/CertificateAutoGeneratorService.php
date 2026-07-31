<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificateAutoGeneratorService
{
    private CertificatePdfService $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Main entry point: Check progress and generate certificate if all lessons are completed.
     *
     * @param int $studentId
     * @param int $courseId
     * @return Certificate|null
     */
    public function generateCertificateIfAllLessonsCompleted(int $studentId, int $courseId): ?Certificate
    {
        try {
            // DB Transaction prevents duplicate generation issues (Race Condition)
            return DB::transaction(function () use ($studentId, $courseId) {
                // Check if certificate already exists
                if ($this->certificateAlreadyExists($studentId, $courseId)) {
                    Log::info("Certificate already exists for student {$studentId} in course {$courseId}.");
                    return null;
                }

                // Check if student completed all lessons
                if (!$this->isAllLessonsCompleted($studentId, $courseId)) {
                    Log::info("Student {$studentId} has not completed all lessons in course {$courseId}.");
                    return null;
                }

                // Generate certificate
                return $this->createCertificate($studentId, $courseId);
            });
        } catch (\Exception $e) {
            Log::error("Failed to generate automatic certificate for student {$studentId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if all lessons in a course are marked as completed for the student.
     *
     * @param int $studentId
     * @param int $courseId
     * @return bool
     */
    public function isAllLessonsCompleted(int $studentId, int $courseId): bool
    {
        // Get all lesson IDs in the course
        $totalLessons = Lesson::whereHas('section', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->count();

        if ($totalLessons === 0) {
            return false;
        }

        // Count how many of these lessons are completed by the student
        $completedLessons = LessonProgress::where('student_id', $studentId)
            ->where('is_completed', true)
            ->whereHas('lesson.section', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->count();

        return $completedLessons === $totalLessons;
    }

    /**
     * Check if a certificate already exists for student in this course.
     *
     * @param int $studentId
     * @param int $courseId
     * @return bool
     */
    private function certificateAlreadyExists(int $studentId, int $courseId): bool
    {
        return Certificate::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * Create the certificate DB record and generate its PDF file.
     *
     * @param int $studentId
     * @param int $courseId
     * @return Certificate
     */
    private function createCertificate(int $studentId, int $courseId): Certificate
    {
        // Find student and course models
        $student = User::find($studentId);
        $course  = Course::find($courseId);

        // Instantiating model instance
        $certificate = new Certificate([
            'student_id' => $studentId,
            'course_id'  => $courseId,
            'issued_at'  => now(),
        ]);

        // Set relations manually before saving so PDF service has access to $student->name and $course->title
        $certificate->setRelation('student', $student);
        $certificate->setRelation('course', $course);

        // Generate PDF using service
        $pdfData = $this->pdfService->generateCertificatePdf($certificate);

        // Fill remaining attributes
        $certificate->certificate_url = $pdfData['certificate_url'];
        $certificate->serial_number   = $pdfData['serial_number'];

        // Save DB record
        $certificate->save();

        Log::info("Certificate created successfully for student {$studentId} in course {$courseId}. ID: {$certificate->id}");

        return $certificate;
    }
}
