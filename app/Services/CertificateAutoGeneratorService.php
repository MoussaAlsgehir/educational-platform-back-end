<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\Log;

class CertificateAutoGeneratorService
{
    private CertificatePdfService $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Check if all lessons are completed and generate a certificate automatically.
     *
     * @param int $studentId
     * @param int $courseId
     * @return Certificate|null
     */
    public function generateCertificateIfAllLessonsCompleted(int $studentId, int $courseId): ?Certificate
    {
        try {
            if (Certificate::where('student_id', $studentId)->where('course_id', $courseId)->exists()) {
                Log::info("Certificate already exists for student {$studentId} in course {$courseId}.");
                return null;
            }

            $totalLessons = Lesson::whereHas('section', fn($q) => $q->where('course_id', $courseId))->count();

            if ($totalLessons === 0) {
                Log::warning("No lessons found for course {$courseId}.");
                return null;
            }

            $completedLessons = LessonProgress::where('student_id', $studentId)
                ->where('is_completed', true)
                ->whereHas('lesson', fn($q) => $q->whereHas('section', fn($sq) => $sq->where('course_id', $courseId)))
                ->count();

            if ($completedLessons < $totalLessons) {
                Log::info("Student {$studentId} has not completed all lessons in course {$courseId}.");
                return null;
            }

            return $this->createCertificate($studentId, $courseId);
        } catch (\Exception $e) {
            Log::error("Failed to generate automatic certificate for student {$studentId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create the certificate and PDF.
     * Prevents '1364' errors by generating the PDF before saving to the database.
     */
    private function createCertificate(int $studentId, int $courseId): Certificate
    {
        // 1. Create an instance in memory
        $certificate = new Certificate([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'issued_at' => now(),
        ]);

        // Load relationships needed for PDF generation
        $certificate->load(['student', 'course']);

        // 2. Generate PDF first
        $pdfData = $this->pdfService->generateCertificatePdf($certificate);

        // 3. Assign generated data to the model
        $certificate->certificate_url = $pdfData['certificate_url'];
        $certificate->serial_number = $pdfData['serial_number'];

        // 4. Save to DB only after all data is present
        $certificate->save();

        Log::info("Certificate created successfully for student {$studentId} in course {$courseId}.");
        return $certificate;
    }

    public function isAllLessonsCompleted(int $studentId, int $courseId): bool
    {
        try {
            $totalLessons = Lesson::whereHas('section', fn($q) => $q->where('course_id', $courseId))->count();
            if ($totalLessons === 0) return false;

            $completedLessons = LessonProgress::where('student_id', $studentId)
                ->where('is_completed', true)
                ->whereHas('lesson', fn($q) => $q->whereHas('section', fn($sq) => $sq->where('course_id', $courseId)))
                ->count();

            return $completedLessons === $totalLessons;
        } catch (\Exception $e) {
            Log::error("Error checking lesson completion: " . $e->getMessage());
            return false;
        }
    }
}
