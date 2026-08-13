<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GeneralNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public $title;
    public $body;
    public $type;

    /**
     * بناء الإشعار ببيانات ديناميكية
     */
    public function __construct(string $title, string $body, string $type = 'general')
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
    }

    /**
     * القنوات المستخدمة (تخزين بقاعدة البيانات + بث لحظي)
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * البيانات التي ستخزن في قاعدة البيانات
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'type'  => $this->type,
        ];
    }

    /**
     * تخصيص البيانات الصافية للبث اللحظي (تمنع الحقول الزائدة)
     */
    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'type'  => $this->type,
        ];
    }

    /**
     * اسم الحدث (Event) الثابت الذي سيستمع إليه الفرونت إند
     */
    public function broadcastType(): string
    {
        return 'GeneralNotification';
    }

        /**
     * البيانات الأساسية للإشعار (تُستخدم للـ Database والـ Broadcast)
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->body,
            'type'  => $this->type,
        ];
    }
}
