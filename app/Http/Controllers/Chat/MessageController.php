<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
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

        $message = $this->msgService->sendMessage($conversation, $request->user(), $request->body);

        // (هون لاحقاً منبعت الـ Event للـ Reverb)

        return ApiResource::sendResponse("Message sent.", new MessageResource($message), 201);
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
