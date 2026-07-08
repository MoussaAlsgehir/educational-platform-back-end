<?php

namespace App\Services;

use App\Models\Quizz;
use App\Models\StudentAttempt;

class QuizService
{
    public function recordAttempt($studentId, $quizId, $score)
    {
        $passingScore = Quizz::find($quizId)->passing_score;

        return StudentAttempt::create([
            'student_id' => $studentId,
            'quiz_id' => $quizId,
            'score' => $score,
            'is_passed' => $score >= $passingScore,
        ]);
    }



}
