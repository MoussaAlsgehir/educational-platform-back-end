<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::routes(['middleware' => ['auth:sanctum']]);
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) return false;

    // 1. الأدمن يقدر يسمع كل المحادثات (للمراقبة)
    if ($user->isAdmin()) return true;

    // 2. شات الكورس أو الإعلانات
    if (in_array($conversation->type, ['course_group', 'announcement']) && $conversation->course_id) {
        return $conversation->course->teacher_id === $user->id
            || $conversation->course->students()->where('student_id', $user->id)->exists();
    }

    // 3. المحادثات الخاصة (Support, Complaint, Teacher-Admin, AI)
    return $conversation->participants()->where('user_id', $user->id)->exists();
});

