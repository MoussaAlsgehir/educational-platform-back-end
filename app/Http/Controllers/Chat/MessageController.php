<?php

namespace App\Http\Controllers\Chat;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\GeneralNotification;
use App\Services\AiService;
use App\Services\Chat\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    protected $msgService;

    public function __construct(MessageService $msgService)
    {
        $this->msgService = $msgService;
    }

    // إرسال رسالة
         public function store(Request $request, Conversation $conversation)
    {
        Gate::authorize('sendMessage', $conversation);
        $request->validate(['body' => 'required|string|max:2000']);

        $message = $this->msgService->sendMessage($conversation, $request->user(), $request->body, false);
        $message->load('user:id,first_name,last_name,avatar_url');
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        //  1. إشعارات الشات (باستثناء الـ AI والـ Group Chat العادي)
        if ($conversation->type === 'announcement') {
            $course = $conversation->course;
            foreach ($course->students as $student) {
                $student->notify(new GeneralNotification(
                    "New Announcement in Course: {$course->title}",
                    $message->body,
                    "announcement"
                ));
            }
        } elseif (in_array($conversation->type, ['student_admin', 'teacher_admin'])) {
            $participants = $conversation->participants()->where('user_id', '!=', $request->user()->id)->get();
            foreach ($participants as $participant) {
                $participant->notify(new GeneralNotification(
                    "New Message",
                    "You have a new message in your support/admin chat.",
                    "chat_message"
                ));
            }
        }

        //  2. إذا المحادثة AI
        if ($conversation->type === 'ai_chat') {
            $aiService = new AiService();
            $aiResponseText = $aiService->generateResponse($conversation, $request->body, $request->user());

            $aiMessage = $this->msgService->sendMessage($conversation, $request->user(), $aiResponseText, true);
            $aiMessage->load('user:id,first_name,last_name,avatar_url');
            broadcast(new MessageSent($aiMessage))->toOthers();

            return ApiResource::sendResponse("Message sent.", new \App\Http\Resources\MessageResource($message), 201);
        }

        return ApiResource::sendResponse("Message sent.", new \App\Http\Resources\MessageResource($message), 201);
    }
    // تعديل رسالة
    public function update(Request $request, Message $message)
    {
        Gate::authorize('update', $message);

        $request->validate(['body' => 'required|string|max:2000']);

        $updated = $this->msgService->editMessage($message, $request->body);
        return ApiResource::sendResponse("Message updated.", new MessageResource($updated));
    }

    // إضافة رد الأستاذ
    public function teacherReply(Request $request, Message $message)
    {
        Gate::authorize('teacherReply', $message);

        $request->validate(['teacher_reply' => 'required|string|max:2000']);

        $updated = $this->msgService->addTeacherReply($message, $request->teacher_reply);

                 $message->user->notify(new GeneralNotification(
            "Teacher Replied to Your Question",
            "The instructor has replied to your message in course: {$message->conversation->course->title}",
            "teacher_reply"
        ));
        return ApiResource::sendResponse("Teacher reply added.", new MessageResource($updated));
    }

    // تثبيت رسالة
    public function pin(Message $message)
    {
        if ($message->conversation->type !== 'course_group') {
            return ApiResource::sendResponse("Pinning is only allowed in course group chats.", null, 422);
        }
        Gate::authorize('pin', $message);

        $updated = $this->msgService->pinMessage($message);
        return ApiResource::sendResponse($updated->is_pinned ? "Message pinned." : "Message unpinned.", new MessageResource($updated));
    }

    // Like / Unlike
    public function toggleLike(Request $request, Message $message)
    {
        Gate::authorize('toggleLike', $message);

        $this->msgService->toggleLike($message, $request->user());

        return ApiResource::sendResponse("Like toggled.", [
            'likes_count' => $message->likes()->count(),
            'is_liked' => $message->isLikedBy($request->user()->id)
        ]);
    }
}
