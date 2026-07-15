<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
