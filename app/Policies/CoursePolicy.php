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

    public function create(User $user)
    {
        return $user->hasRole('instructor');
    }

    public function update(User $user, Course $course)
    {
        $isOwner = $user->hasRole('instructor') && $course->teacher_id === $user->id;

        $isEditable = !$course->is_published || $course->is_editable;

        return $isOwner && $isEditable;
    }

    public function delete(User $user, Course $course)
    {
        $isOwner = $user->hasRole('instructor') && $course->teacher_id === $user->id;

        $isDeletable = $course->status=='draft' ;

        return $isOwner && $isDeletable;
    }

    public function view(User $user, Course $course)
    {
        return $user->hasRole('instructor') && $course->teacher_id === $user->id;
    }
}
