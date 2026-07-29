<?php

namespace App\Models;

use Attribute;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute as CastsAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{

    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'certificate_attendance_threshold',
        'teacher_id',
        'cover_image',
        'price',
        'course_type',
        'status',
    ];


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_course');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class, 'course_id', 'section_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CourseAttachment::class);
    }

    public function reviews()
    {
        return $this->hasMany(CourseReview::class, 'course_id');
    }


    public function students()
    {
        return $this->belongsToMany(User::class, 'student_enrollments_course', 'course_id', 'student_id')
            ->withPivot(['attendance_percentage', 'is_completed'])
            ->withTimestamps();
    }


    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'course_id');
    }

    public function categoryCourses(){
        return $this->hasMany(CourseCategory::class);
    }
}
