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
            'status' => 'pending',
        ]);

        if (isset($data['category_ids'])) {
            $course->categorys()->sync($data['category_ids']);
        }

        return $course;
    }
}
