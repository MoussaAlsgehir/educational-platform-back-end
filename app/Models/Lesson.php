<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
