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
        'is_published',
        'navigation_type',
        'rejection_reason',
        'publish_type',
        'expected_sections_count',
        'is_editable',
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


    //scopes


    public function scopeAvailable($query)
    {
        return $query->whereNotIn('status', ['pending', 'rejected']);
    }
    public function scopeStatusFilter($query,$status)
    {
        if ($status) {
             $query->where('status', $status);
        }
        return $query;
    }


    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where(function ($q) use ($term) {
                $q->where('title', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }
        return $query;
    }


      public function scopeCategoryFilter($query, $categoryIds)
    {
        if (is_array($categoryIds)) {
            $categoryIds = array_filter($categoryIds, function($value) {
                return !is_null($value) && $value !== '';
            });
        }

        if (!empty($categoryIds)) {
            return $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        return $query;
    }


    public function scopePriceFilter($query, $minPrice, $maxPrice)
    {
        if ($minPrice !== null) $query->where('price', '>=', $minPrice);
        if ($maxPrice !== null) $query->where('price', '<=', $maxPrice);
        return $query;
    }
}
