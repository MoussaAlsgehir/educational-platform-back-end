<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Certificate extends Model
{
    // تحديد اسم الجدول بشكل صريح لأن الاسم مختلف عن اسم الموديل
    protected $table = 'student_certificates_course';

    // الحقول المسموح بتعبئتها (Mass Assignment)
    protected $guarded = ['id'];

    // تحديد الحقول التي هي عبارة عن تواريخ (لتسهيل التعامل معها كـ Carbon Objects)
    protected $dates = ['issued_at'];

    // الحقول التي يجب الحفاظ على قيمها
    protected $fillable = [
        'student_id',
        'course_id',
        'certificate_url',
        'serial_number',
        'issued_at',
    ];

    // العلاقة مع الطالب
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // العلاقة مع الكورس
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
