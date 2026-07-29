<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseAttachment;
use Illuminate\Database\Seeder;

class CourseAttachmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        if ($courses->isEmpty()) {
            $this->command->warn('No courses found. Skipping CourseAttachmentSeeder.');
            return;
        }

        $attachments = [
            [
                'type' => 'pdf',
                'file_url' => 'course_attachments/syllabus.pdf',
            ],
            [
                'type' => 'doc',
                'file_url' => 'course_attachments/study_guide.docx',
            ],
            [
                'type' => 'link',
                'file_url' => 'https://github.com/example/project-repo',
            ],
        ];

        foreach ($courses as $course) {
            foreach ($attachments as $attachmentData) {
                CourseAttachment::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'type' => $attachmentData['type'],
                        'file_url' => $attachmentData['file_url'],
                    ],
                    $attachmentData
                );
            }
        }

        $this->command->info('Course attachments created successfully.');
    }
}
