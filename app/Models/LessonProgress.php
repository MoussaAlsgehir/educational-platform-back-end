<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property int $lesson_id
 * @property int $watched_seconds
 * @property bool $is_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Lesson $lesson
 * @property-read \App\Models\User $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress forStudent($studentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereIsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereLessonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LessonProgress whereWatchedSeconds($value)
 * @mixin \Eloquent
 */
class LessonProgress extends Model
{
    protected $table = 'student_progress_lesson';
    protected $guarded = ['id'];

    protected $casts = [
        'is_completed' => 'boolean',
        'watched_seconds' => 'integer',
    ];

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // الطالب صاحب هذا السجل
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // الدرس المرتبط بهذا السجل
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }
}
