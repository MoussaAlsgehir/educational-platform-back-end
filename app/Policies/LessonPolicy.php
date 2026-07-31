<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * نغلة الـ Create: بنمرر كائن الكورس الأب لنشيك على ملكيته قبل إضافة أي درس جواته
     */
    public function create(User $user, Course $course)
    {
        return $user->hasRole('instructor') && $course->teacher_id === $user->id;
    }

    /**
     * تعديل الدرس أو حذفه يعتمد على ملكية المدرس للكورس الأب للدرس الحالي
     */
    public function update(User $user, Lesson $lesson)
    {
        $isOwner = $user->hasRole('instructor') && $lesson->course->teacher_id === $user->id;
        $isEditable = !$lesson->course->is_published || $lesson->course->is_editable || $lesson->course->status !== 'completed';

        return $isOwner && $isEditable;
    }

    public function delete(User $user, Lesson $lesson)
    {
        $isOwner = $user->hasRole('instructor') && $lesson->course->teacher_id === $user->id;
        $isDeletable = !in_array($lesson->course->status, ['completed', 'active', 'upcoming']);

        return $isOwner && $isDeletable;
    }
}
