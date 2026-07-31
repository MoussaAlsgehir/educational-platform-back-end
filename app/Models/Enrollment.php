<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property int $course_id
 * @property numeric $attendance_percentage
 * @property bool $is_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course $course
 * @property-read \App\Models\User $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereAttendancePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereIsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Enrollment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Enrollment extends Model
{
protected $table = 'student_enrollments_course';

protected $guarded = ['id'];
protected $casts = [
'is_completed' => 'boolean',
'attendance_percentage' => 'decimal:2',
];

public function student()
{
return $this->belongsTo(User::class, 'student_id');
}

public function course()
{
return $this->belongsTo(Course::class, 'course_id');
}
}
