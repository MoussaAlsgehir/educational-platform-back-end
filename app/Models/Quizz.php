<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property int $passing_score
 * @property int $order_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Question> $questions
 * @property-read int|null $questions_count
 * @property-read \App\Models\Section $section
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StudentAttempt> $studentAttempts
 * @property-read int|null $student_attempts_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz wherePassingScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Quizz whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Quizz extends Model
{
    // تحديد اسم الجدول والمحاذير كما اخترتها تماماً
    protected $table = 'quizzs';
    protected $guarded = ['id','order_number'];

    /**
     * الـ Boot Method لحساب الترتيب تلقائياً على مستوى الكورس بالكامل عند الإنشاء
     */
    protected static function booted()
    {
        static::creating(function ($quizz) {
            $section = Section::find($quizz->section_id);

            if ($section) {
                $maxOrder = DB::table('quizzs')
                    ->join('sections', 'quizzs.section_id', '=', 'sections.id')
                    ->where('sections.course_id', $section->course_id)
                    ->max('quizzs.order_number');

                $quizz->order_number = $maxOrder ? $maxOrder + 1 : 1;
            }
        });
    }

    /**
     * العلاقة: الكويز ينتمي إلى قسم واحد (1:1)
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    public function questions(){
        return $this->hasMany(Question::class);
    }

    public function studentAttempts()
    {
        return $this->hasMany(StudentAttempt::class, 'quiz_id');
    }
}
