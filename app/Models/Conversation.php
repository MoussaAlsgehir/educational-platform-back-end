<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    protected $fillable = [
        'course_id', 'type', 'subtype', 'status',
        'active_by', 'active_at', 'subject_type', 'subject_id'
    ];

    protected $casts = [
        'active_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function activeAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'active_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('read_at');

    }


    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // هل المحادثة مقفولة على أدمن معين؟
    public function isLockedByAnotherAdmin($userId): bool
    {
        return $this->active_by !== null && $this->active_by !== $userId;
    }
}
