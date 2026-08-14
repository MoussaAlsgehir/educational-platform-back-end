<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $data = [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'body' => $this->body,
            'is_edited' => $this->is_edited,
            'is_pinned' => $this->is_pinned,
            'is_ai_response' => $this->is_ai_response,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'edited_at' => $this->when($this->is_edited, function () {
                return $this->updated_at->format('Y-m-d H:i:s');
            }),

            // معلومات المرسل (تظهر بس إذا تم تحميلها بـ with)
            'sender' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar_url' => $this->user->avatar_url
                        ? asset('storage/' . $this->user->avatar_url)
                        : asset('storage/avatars/default-avatar.jpg'),
                ];
            }),
        ];

        // رد الأستاذ يظهر بس إذا كان موجود
        if ($this->teacher_reply) {
            $data['teacher_reply'] = $this->teacher_reply;
        }

        // بيانات الإعجاب (Likes)
        $data['likes'] = [
            // بنفحص إذا الـ likes_count محملة عبر withCount، وإذا لأ بنحسبها
            'count' => $this->whenCounted('likes', $this->likes_count, 0),
            'is_liked' => $user ? $this->isLikedBy($user->id) : false,
        ];

        return $data;
    }
}
