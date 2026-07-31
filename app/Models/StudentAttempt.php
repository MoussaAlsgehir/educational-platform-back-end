<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class StudentAttempt extends Model
{
    protected $table = 'student_attempts_quiz';
    protected $guarded = ['id'];

    public function student()
    {
        return $this->belongsTo(User::class);
    }
    public function quiz()
    {
        return $this->belongsTo(Quizz::class);
    }

    public static function hasPassed($studentId, $quizId)
    {

        return self::where('student_id', $studentId)->where('quiz_id', $quizId)->where('is_passed', true)->exists();
    }


}
