<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    // إنشاء محادثة دعم
    // إنشاء محادثة دعم
    public function createSupport(User $student): Conversation
    {
        //  نفحص إذا الطالب إلو محادثة دعم مفتوحة سابقة
        $existing = Conversation::where('type', 'student_admin')
            ->where('subtype', 'support')
            ->where('status', 'open')
            ->whereHas('participants', function ($q) use ($student) {
                $q->where('user_id', $student->id);
            })->first();

        // إذا موجودة، رجعها وما تنشئ جديدة
        if ($existing) return $existing;

        // إذا ما في، انشئ واحدة جديدة
        $conversation = Conversation::create([
            'type' => 'student_admin',
            'subtype' => 'support',
            'status' => 'open'
        ]);

        $conversation->participants()->attach($student->id);
        return $conversation;
    }
    // إنشاء شكوى (مع الـ Subject)
    public function createComplaint(User $student, string $content, string $subjectType = null, int $subjectId = null): Conversation
    {
        // Whitelist للأنواع المسموح الشكوى عليها
        $allowedTypes = ['Message', 'User', 'Course', 'Lesson'];

        $conversation = Conversation::create([
            'type' => 'student_admin',
            'subtype' => 'complaint',
            'status' => 'open',
            'subject_type' => in_array($subjectType, $allowedTypes) ? "App\\Models\\$subjectType" : null,
            'subject_id' => $subjectId,
        ]);

        $conversation->participants()->attach($student->id);

        // نضيف رسالة الشكوى كأول رسالة
        $conversation->messages()->create([
            'user_id' => $student->id,
            'body' => $content
        ]);

        return $conversation;
    }


        // إنشاء محادثة الذكاء الاصطناعي للطالب
    public function createAiChat(User $student): Conversation
    {
        // نتأكد إذا الطالب إلو محادثة AI مفتوحة سابقاً (عشان ما نفتحلو كتير)
        $existing = Conversation::where('type', 'ai_chat')
            ->where('status', 'open')
            ->whereHas('participants', function ($q) use ($student) {
                $q->where('user_id', $student->id);
            })->first();

        if ($existing) return $existing;

        $conversation = Conversation::create([
            'type' => 'ai_chat',
            'status' => 'open'
        ]);

        $conversation->participants()->attach($student->id);
        return $conversation;
    }
    // استلام المحادثة من قبل الأدمن (Active Lock)
    public function activate(Conversation $conversation, User $admin): Conversation
    {
        if ($conversation->active_by !== null && $conversation->active_by !== $admin->id) {
            throw new \Exception("This conversation is already active by another admin.");
        }

        $conversation->update([
            'active_by' => $admin->id,
            'active_at' => now()
        ]);

        return $conversation;
    }

    // ترك المحادثة من قبل الأدمن
    public function release(Conversation $conversation, User $admin): Conversation
    {
        if ($conversation->active_by !== $admin->id) {
            throw new \Exception("You cannot release a conversation you did not activate.");
        }

        $conversation->update([
            'active_by' => null,
            'active_at' => null
        ]);

        return $conversation;
    }

    // إغلاق المحادثة
    public function close(Conversation $conversation): Conversation
    {
        $newStatus = $conversation->status === 'closed' ? 'open' : 'closed';

        $conversation->update([
            'status' => $newStatus,
            'active_by' => $newStatus === 'closed' ? null : $conversation->active_by,
            'active_at' => $newStatus === 'closed' ? null : $conversation->active_at,
        ]);

        return $conversation;
    }

    // تحديث القراءة (Read At)
    public function markAsRead(Conversation $conversation, User $user): void
    {
        DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->update(['read_at' => now()]);
    }
}
