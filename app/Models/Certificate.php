<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $course_id
 * @property string $certificate_url
 * @property string $serial_number
 * @property string $issued_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Course $course
 * @property-read \App\Models\User $student
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereCertificateUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereCourseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereStudentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certificate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Certificate extends Model
{
    // تحديد اسم الجدول بشكل صريح لأن الاسم مختلف عن اسم الموديل
    protected $table = 'student_certificates_course';

    // الحقول المسموح بتعبئتها (Mass Assignment)
    protected $guarded = ['id'];

    // تحديد الحقول التي هي عبارة عن تواريخ (لتسهيل التعامل معها كـ Carbon Objects)
    protected $dates = ['issued_at'];

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
