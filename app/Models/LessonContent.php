<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonContent extends Model
{
    use HasFactory;

    protected $table = 'lesson_contents';
    protected $fillable = [
        'lesson_id',
        'type',
        'title',
        'text_value',
        'duration',
        'storage_key',
        'status',
        'order'
    ];
    //ثابتات لحالات الوسائط لتجنب الأخطاء الإملائية في الكود
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY      = 'ready';
    public const STATUS_FAILED     = 'failed';

        //ثابتات لأنواع الوسائط لتجنب الأخطاء الإملائية في الكود
    public const TYPES_VIDEO        ='video';
    public const TYPES_TEXT_ARTICLE ='text_article';
    public const TYPES_PDF          ='pdf';
    public const TYPES_quiz         ='quiz';

    public function lesson() : BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }




}
