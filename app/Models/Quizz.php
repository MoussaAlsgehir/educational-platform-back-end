<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
}
