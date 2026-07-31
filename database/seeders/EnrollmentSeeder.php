<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })->get();

        $courses = Course::all();

        if ($students->isEmpty() || $courses->isEmpty()) {
            $this->command->warn('No students or courses found. Skipping EnrollmentSeeder.');
            return;
        }

        // Enroll each student in each course with varying attendance
        foreach ($students as $index => $student) {
            // First student gets all courses, others get random subset
            $coursesToEnroll = ($index === 0) ? $courses : $courses->random(min(2, $courses->count()));

            foreach ($coursesToEnroll as $course) {
                // Determine random attendance and completion status
                $attendancePercentage = rand(30, 100);
                $isCompleted = $attendancePercentage >= 80; // Completed only if attendance >= 80%

                DB::table('student_enrollments_course')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ],
                    [
                        'attendance_percentage' => $attendancePercentage,
                        'is_completed' => $isCompleted,
                        'created_at' => now()->subDays(rand(10, 60)),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Student enrollments created successfully.');
    }
}
