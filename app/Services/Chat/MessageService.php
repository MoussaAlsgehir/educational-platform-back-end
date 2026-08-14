<?php

namespace App\Services\Chat;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MessageService
{
    // إرسال رسالة
    public function sendMessage($conversation, User $user, string $body): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);
    }

    // تعديل رسالة
    public function editMessage(Message $message, string $body): Message
    {
        $message->update([
            'body' => $body,
            'is_edited' => true
        ]);

        return $message;
    }

    // إضافة/تعديل رد الأستاذ
    public function addTeacherReply(Message $message, string $reply): Message
    {
        $message->update(['teacher_reply' => $reply]);
        return $message;
    }

    // تثبيت رسالة
    public function pinMessage(Message $message): Message
    {
        $message->update(['is_pinned' => !$message->is_pinned]); // Toggle
        return $message;
    }

    // Like / Unlike
    public function toggleLike(Message $message, User $user): void
    {
        DB::transaction(function () use ($message, $user) {
            if ($message->isLikedBy($user->id)) {
                $message->likes()->where('user_id', $user->id)->delete();
            } else {
                $message->likes()->create(['user_id' => $user->id]);
            }
        });
    }
}
