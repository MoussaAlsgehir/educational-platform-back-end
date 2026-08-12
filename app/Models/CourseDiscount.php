<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CourseDiscount extends Model
{
    protected $fillable = [
        'course_id', 'percentage', 'status', 'type', 'starts_at', 'ends_at', 'rejection_reason'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     *  دالة مساعدة: هل الخصم مفعل حالياً وصالح للاستخدام؟
     */
    public function isActive(): bool
    {
        if ($this->status !== 'approved') return false;

        if ($this->type === 'permanent') return true;

        $now = Carbon::now();
        return $now->gte($this->starts_at) && $now->lte($this->ends_at);
    }
}
