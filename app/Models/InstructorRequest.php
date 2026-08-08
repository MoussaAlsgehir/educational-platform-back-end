<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorRequest extends Model
{
    protected $fillable = [
        'user_id',
        'specialization',
        'cv_url',
        'status',
        'rejection_reason'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     public function getCvUrlAttribute($value)
    {
        if (!$value) return null;

        // إذا كان الرابط كامل (مثلاً من بيانات قديمة) خليه زي ما هو
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // إذا كان مسار نسبي، ركب عليه رابط السحابة
        $workerUrl = rtrim(env('CLOUDFLARE_WORKER_URL'), '/');
        return "{$workerUrl}/{$value}";
    }
}
