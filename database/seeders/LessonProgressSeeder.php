<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonProgressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })->get();

        $lessons = Lesson::all();

        if ($students->isEmpty() || $lessons->isEmpty()) {
            $this->command->warn('No students or lessons found. Skipping LessonProgressSeeder.');
            return;
        }

        foreach ($students as $index => $student) {
            foreach ($lessons as $lesson) {
                // First student (Ahmed) completes ALL lessons fully
                // Others complete random lessons
                if ($index === 0) {
                    $isCompleted = true;
                    $watchedSeconds = $lesson->contents()->where('type', 'video')->first()?->duration ?? 300;
                } else {
                    $isCompleted = (bool) rand(0, 1);
                    $watchedSeconds = $isCompleted
                        ? ($lesson->contents()->where('type', 'video')->first()?->duration ?? 300)
                        : rand(0, 120);
                }

                LessonProgress::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'lesson_id' => $lesson->id,
                    ],
                    [
                        'watched_seconds' => $watchedSeconds,
                        'is_completed' => $isCompleted,
                    ]
                );
            }
        }

        $this->command->info('Lesson progress created successfully.');
    }
}
