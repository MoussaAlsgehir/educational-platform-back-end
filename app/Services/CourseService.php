<?php
namespace App\Services;
use App\Models\Course;
class CourseService
{
    public function createCourse(array $data , int $teacherId): Course
    {
        //انشاء الكورس من قبل المعلم وربطه بالتصنيفات
        $course = Course::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'course_type' => $data['course_type'] ?? 'quiz_based',
            'price' => $data['price'],
            'teacher_id' => $teacherId,
            'start_date' => $data['start_date'] ?? now()->toDateString(),//تعيين تاريخ البدء إلى اليوم إذا لم يتم توفيره
            'end_date' => $data['end_date'] ?? now()->addYears(3)->toDateString(),//تعيين تاريخ الانتهاء إلى 3 سنوات من اليوم إذا لم يتم توفيره
            'certificate_attendance_threshold' => $data['certificate_attendance_threshold'] ?? 60,//تعيين نسبة الحضور المطلوبة للحصول على الشهادة إلى 60% إذا لم يتم توفير
            'status' => 'pending',
        ]);

        if (isset($data['category_ids'])) {
            $course->categorys()->sync($data['category_ids']);
        }

        return $course;
    }
}
