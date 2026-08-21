<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability, Message $message = null)
    {

        if ($user->isAdmin()) {
            return true;
        }
    }


    public function update(User $user, Message $message)
    {
        return $message->user_id === $user->id;
    }

    // تثبيت الرسالة: المدرس تبع الكورس بس، والرسالة لازم يكون فيها رد
    public function pin(User $user, Message $message)
    {
        $course = $message->conversation->course;
        return $course && $course->teacher_id === $user->id && !is_null($message->teacher_reply);
    }

    // إضافة رد الأستاذ: المدرس تبع الكورس بس
    public function teacherReply(User $user, Message $message)
    {
        $course = $message->conversation->course;
        return $course && $course->teacher_id === $user->id;
    }

    // الإعجاب: الطالب يقدر يعمل لايك لرسالة بمحادثة كورس (الإعلانات ممنوعة)
    public function toggleLike(User $user, Message $message)
    {
        return $message->conversation->type === 'course_group';
    }
}
