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
        return $user->hasRole('instructor') && $lesson->course->teacher_id === $user->id;
    }

    public function delete(User $user, Lesson $lesson)
    {
        return $user->hasRole('instructor') && $lesson->course->teacher_id === $user->id;
    }
}
