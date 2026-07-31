<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


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
