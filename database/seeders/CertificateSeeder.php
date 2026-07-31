<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CertificatePdfService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CertificateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get students who have completed the course (enrollment is_completed = true)
        $completedEnrollments = Enrollment::where('is_completed', true)->get();

        if ($completedEnrollments->isEmpty()) {
            $this->command->warn('No completed enrollments found. Skipping CertificateSeeder.');
            return;
        }

        $pdfService = new CertificatePdfService();
        $createdCount = 0;

        foreach ($completedEnrollments as $enrollment) {
            // Skip if certificate already exists for this student + course
            $existingCertificate = Certificate::where('student_id', $enrollment->student_id)
                ->where('course_id', $enrollment->course_id)
                ->first();

            if ($existingCertificate) {
                continue;
            }

            try {
                // Create the certificate instance in memory first
                $certificate = new Certificate([
                    'student_id' => $enrollment->student_id,
                    'course_id' => $enrollment->course_id,
                    'issued_at' => now(),
                ]);

                // Load relationships for PDF generation
                $certificate->load(['student', 'course']);

                // Generate the actual PDF file using the service
                $pdfData = $pdfService->generateCertificatePdf($certificate);

                // Assign the generated data
                $certificate->certificate_url = $pdfData['certificate_url'];
                $certificate->serial_number = $pdfData['serial_number'];

                // Save to database
                $certificate->save();

                $createdCount++;
                $this->command->info("Certificate created for student ID {$enrollment->student_id} in course ID {$enrollment->course_id}.");
            } catch (\Exception $e) {
                Log::error("Failed to create certificate for student {$enrollment->student_id}, course {$enrollment->course_id}: " . $e->getMessage());
                $this->command->warn("Failed to create certificate: " . $e->getMessage());
            }
        }

        $this->command->info("{$createdCount} certificates created successfully with PDF files.");
    }
}
