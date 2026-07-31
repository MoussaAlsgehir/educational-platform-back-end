<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $course_id
 * @property int|null $section_id
 * @property string|null $title
 * @property string $type
 * @property string $file_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course $course
 * @property-read \App\Models\Section|null $section
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereFileUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CourseAttachment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CourseAttachment extends Model
{
    protected $fillable = [
        'course_id',
        'section_id',
        'title', 
        'type',
        'file_url'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
