<?php
namespace App\Services;
use App\Models\Course;
use Carbon\Carbon;

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
            'start_date' => Carbon::parse($data['start_date'])->format('Y-m-d') ?? now()->format('Y-m-d'),//تعيين تاريخ البدء إلى اليوم إذا لم يتم توفيره
            'end_date' => Carbon::parse($data['end_date'])->format('Y-m-d') ?? $data['end_date'] ?? now()->addMonths(3)->format('Y-m-d'),//تعيين تاريخ الانتهاء إلى 3 شهور من اليوم إذا لم يتم توفيره
            'certificate_attendance_threshold' => $data['certificate_attendance_threshold'] ?? 60,//تعيين نسبة الحضور المطلوبة للحصول على الشهادة إلى 60% إذا لم يتم توفير
            'status' => 'pending',
            'cover_image' => $data['cover_image'] ?? null,
        ]);

        if (isset($data['category_ids'])) {
            $course->categories()->sync($data['category_ids']);
        }
     

        return $course;

}
}
