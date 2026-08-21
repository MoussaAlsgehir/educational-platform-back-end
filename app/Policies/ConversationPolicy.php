<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Course;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        //if ($user->isAdmin()) return true; // الأدمن يقدر يعمل كلشي
    }

    // هل يقدر يشوف المحادثة؟
       public function view(User $user, Conversation $conversation)
    {
        // 1. محادثة الكورس أو الإعلان
        if (in_array($conversation->type, ['course_group', 'announcement']) && $conversation->course_id) {
            return $conversation->course->teacher_id === $user->id
                || $conversation->course->students()->where('student_id', $user->id)->exists();
        }

        // 2. محادثة الـ AI (خاصة بالطالب صاحبها فقط)
        if ($conversation->type === 'ai_chat') {
            return $conversation->participants()->where('user_id', $user->id)->exists();
        }

        // 3. المحادثات الخاصة (support/complaint/teacher_admin)
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    // هل يقدر يبعث رسالة؟
      // هل يقدر يبعث رسالة؟
    public function sendMessage(User $user, Conversation $conversation)
    {
        // المحادثة لازم تكون مفتوحة
        if ($conversation->status === 'closed') return false;

        // الإعلانات: الطلاب ممنوعين من الإرسال
        if ($conversation->type === 'announcement') {
            return $user->hasRole('instructor') || $user->isAdmin();
        }

        // محادثات الدعم والشكاوى (student_admin و teacher_admin)
        if (in_array($conversation->type, ['student_admin', 'teacher_admin'])) {

            // = الأدمن: يجب أن يكون هو الـ active_by ليرسل رسالة
            if ($user->isAdmin()) {
                return $conversation->active_by === $user->id;
            }

            // الطالب/المدرس: يقدروا يبعثوا دائماً (ما دام المحادثة مفتوحة)
            return $this->view($user, $conversation);
        }

        // غير هيك (شات الكورس)، يقدر يبعث إذا كان مسموحلو يشوف المحادثة
        return $this->view($user, $conversation);
    }
}
