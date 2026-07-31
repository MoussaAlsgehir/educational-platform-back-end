<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property int $quiz_id
 * @property int $score
 * @property int $is_passed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Quizz $quiz
 * @property-read \App\Models\User $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereIsPassed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereQuizId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StudentAttempt whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
