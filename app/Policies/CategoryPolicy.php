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
        return $user->hasRole('teacher');
    }

    /**
     * هل المدرس الحالي هو صاحب الكورس ليعدله؟
     */
    public function update(User $user, Course $course)
    {
        return $user->hasRole('teacher') && $course->teacher_id === $user->id;
    }


    public function delete(User $user, Course $course)
    {
        return $user->hasRole('teacher') && $course->teacher_id === $user->id;
    }
}
