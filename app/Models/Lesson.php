<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property int $order
 * @property bool $is_preview
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LessonContent> $contents
 * @property-read int|null $contents_count
 * @property-read \App\Models\Course|null $course
 * @property-read \App\Models\Section $section
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LessonProgress> $studentProgress
 * @property-read int|null $student_progress_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereIsPreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Lesson whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Lesson extends Model
{
    use HasFactory;

        protected $fillable = [
        'title',
        'is_preview',
        'duration',
        'order',
        'section_id',
        'content',
    ];

    protected $casts = [
        'is_preview' => 'boolean',
    ];

    public function section() : BelongsTo
    {
        return $this->belongsTo(Section::class)->orderBy('order', 'asc');
    }

    public function contents() : HasMany
    {
        return $this->hasMany(LessonContent::class)->orderBy('order', 'asc');
    }


    public function studentProgress()
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }


    public function course()
    {
       return $this->belongsTo(Course::class);
    }
}
