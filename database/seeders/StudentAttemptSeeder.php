<?php

namespace Database\Seeders;

use App\Models\Quizz;
use App\Models\StudentAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::whereHas('roles', function ($q) {
            $q->where('name', 'student');
        })->get();

        $quizzes = Quizz::all();

        if ($students->isEmpty() || $quizzes->isEmpty()) {
            $this->command->warn('No students or quizzes found. Skipping StudentAttemptSeeder.');
            return;
        }

        foreach ($students as $student) {
            foreach ($quizzes as $quiz) {
                // Generate a random score between 40 and 100
                $score = rand(40, 100);
                $isPassed = $score >= $quiz->passing_score;

                StudentAttempt::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'quiz_id' => $quiz->id,
                    ],
                    [
                        'score' => $score,
                        'is_passed' => $isPassed,
                    ]
                );
            }
        }

        $this->command->info('Student quiz attempts created successfully.');
    }
}
