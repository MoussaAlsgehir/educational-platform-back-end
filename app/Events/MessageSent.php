<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // القناة يلي رح يتبث عليها (قناة المحادثة الخاصة)
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    // البيانات يلي رح تصل للفرونت إند
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'body' => $this->message->body,
            'is_ai_response' => $this->message->is_ai_response,
            'created_at' => $this->message->created_at->toDateTimeString(),
            'sender' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar_url ? asset('storage/' . $this->message->user->avatar_url) : null,
            ]
        ];
    }
}
