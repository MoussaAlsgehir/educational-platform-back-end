<?php
namespace App\Services;
use App\Models\Course;
use Carbon\Carbon;

class CourseService
{
        public function createCourse(array $data, int $teacherId): Course
    {
        $courseType = $data['course_type'] ?? 'quiz_based';
        $navigationType = $data['navigation_type'] ?? 'free';

       
        if ($courseType === 'attendance_only') {
            $navigationType = 'free';
        }

        $course = Course::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'course_type' => $courseType,
            'publish_type' => $data['publish_type'] ?? 'on_demand',
            'navigation_type' => $navigationType,
            'price' => $data['price'],
            'teacher_id' => $teacherId,
            'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : null,
            'end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date'])->format('Y-m-d') : null,
            'certificate_attendance_threshold' => $data['certificate_attendance_threshold'] ?? 60,
            'status' => 'draft',
            'expected_sections_count' => $data['expected_sections_count'] ?? 0,
            'cover_image' => $data['cover_image'] ?? null,
        ]);

        if (isset($data['category_ids'])) {
            $course->categories()->sync($data['category_ids']);
        }

        return $course;
    }
}
