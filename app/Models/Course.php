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


        public function activeDiscount()
    {
        return $this->hasOne(CourseDiscount::class)->where('status', 'approved')->latest();
    }

    // علاقة عامة لكل الخصومات (للإدارة)
    public function discounts()
    {
        return $this->hasMany(CourseDiscount::class);
    }

        /**
     * حساب السعر النهائي بعد التحقق من الخصم الفعال
     */
    public function getFinalPrice(): float
    {
        $originalPrice = (float) $this->price;


        if (!$this->relationLoaded('activeDiscount')) {
            $this->load('activeDiscount');
        }

        if ($this->activeDiscount && $this->activeDiscount->isActive()) {
            $discountPercentage = (float) $this->activeDiscount->percentage;
            return $originalPrice - ($originalPrice * ($discountPercentage / 100));
        }

        return $originalPrice;
    }


    public function getTotalMinutes(): float

    {

                if ($this->relationLoaded('sections') && $this->sections->isNotEmpty()) {
                    $totalSeconds = 0;
                    foreach ($this->sections as $section) {
                        if ($section->relationLoaded('lessons')) {
                            foreach ($section->lessons as $lesson) {
                                if ($lesson->relationLoaded('contents')) {
                                    foreach ($lesson->contents as $content) {
                                        $totalSeconds += $content->duration ?? 0;
                                    }
                                }
                            }
                        }
                    }
                    return round($totalSeconds / 60, 1);
                }
                return 0;

    }

        /**
     * حساب مؤشرات التفاعل وسرعة الاستجابة
     */
    public function getInteractionIndicators(): array
    {
        $conversation = \App\Models\Conversation::where('course_id', $this->id)->where('type', 'course_group')->first();

        if (!$conversation) {
            return ['chat_activity' => 'weak', 'response_speed' => 'slow'];
        }

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        // 1. مؤشر التفاعل
        $chatActivity = 'weak';
        if ($messages->count() > 50) {
            $chatActivity = 'strong';
        } elseif ($messages->count() > 10) {
            $chatActivity = 'normal';
        }

        // 2. مؤشر سرعة الاستجابة
        $responseSpeed = 'slow';
        $responseTimes = [];
        $lastStudentMessageTime = null;

        foreach ($messages as $message) {
            if ($message->user_id !== $this->teacher_id) {
                $lastStudentMessageTime = $message->created_at;
            } elseif ($lastStudentMessageTime) {
                $responseTimes[] = $lastStudentMessageTime->diffInMinutes($message->created_at);
                $lastStudentMessageTime = null;
            }
        }

        if (count($responseTimes) > 0) {
            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
            if ($avgResponseTime < 60) {
                $responseSpeed = 'fast';
            } elseif ($avgResponseTime < 1440) {
                $responseSpeed = 'normal';
            }
        }

        return [
            'chat_activity' => $chatActivity,
            'response_speed' => $responseSpeed
        ];
    }

    //scopes


    public function scopeAvailable($query)
    {
        return $query->whereNotIn('status', ['pending', 'rejected','draft','hidden']);
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
    public function categoryCourses(){
        return $this->hasMany(CourseCategory::class);
    }
}
