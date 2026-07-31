<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Course;
use Illuminate\Auth\Access\HandlesAuthorization;

class CoursePolicy
{
    use HandlesAuthorization;

    // الأدمن يمر فوق كل القوانين
    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * هل مسموح للمستخدم إنشاء كورس؟
     */
    public function create(User $user)
    {
        return $user->hasRole('instructor');
    }

    /**
     * هل المدرس الحالي هو صاحب الكورس ليعدله؟
     */
    public function update(User $user, Course $course)
    {

        $isOwner = $user->hasRole('instructor') && $course->teacher_id === $user->id;
        $isEditable = !$course->is_published || $course->is_editable || $course->status !== 'completed';

        return $isOwner && $isEditable;

    }

    public function delete(User $user, Course $course)
    {
        $isOwner = $user->hasRole('instructor') && $course->teacher_id === $user->id;
        $isDeletable = !in_array($course->status, ['completed', 'active', 'upcoming']);

        return $isOwner && $isDeletable;
    }
}
