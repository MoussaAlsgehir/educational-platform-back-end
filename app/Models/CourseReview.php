<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $course_id
 * @property int $student_id
 * @property int $rating
 * @property string|null $review_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course $course
 * @property-read \App\Models\User $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereReviewText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseReview whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CourseReview extends Model
{
    use HasFactory;

    protected $table = 'course_reviews';

    protected $guarded = ['id'];
    /**
     * العلاقة: المراجعة تنتمي إلى كورس معين
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * العلاقة: المراجعة كُتبت بواسطة طالب (مستخدم) معين
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
