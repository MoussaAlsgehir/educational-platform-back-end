<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'type'   => $this->type,
            'title'  => $this->title,
            'order'  => $this->order,
            'status' => $this->status,

            // 1. حقول تظهر فـقـط إذا كان المحتوى مقال نصي (text_article)
            $this->mergeWhen($this->type === 'text_article', [
                'text_value' => $this->text_value,
            ]),

            // 2. حقول تظهر فـقـط إذا كان المحتوى ملف مرفق (pdf)
            $this->mergeWhen($this->type === 'pdf', [
                'download_url' => $this->storage_key ? asset('storage/' . $this->storage_key) : null,
            ]),

            // 3. حقول تظهر فـقـط إذا كان المحتوى فيديو (video)
            
            $this->mergeWhen($this->type === 'video', [
                'duration'     => $this->duration,
                'playback_url' => $this->generateCloudflareWorkerUrl(), // 🔥 الرابط السحري الجديد
            ]),
        ];
    }

    /**
     * بناء رابط التشغيل التكيفي (HLS) ليمر عبر حارس الحماية بـ Cloudflare Worker
     */
    private function generateCloudflareWorkerUrl(): ?string
    {
        // إذا الفيديو لسا عم يتجفز (Processing) أو ما له مسار، ما بترجع رابط
        if ($this->status !== 'ready' || !$this->storage_key) {
            return null;
        }

        // 1. جلب رابط الـ Cloudflare Worker من ملف الـ .env وتنظيفه
        $workerUrl = rtrim(env('CLOUDFLARE_WORKER_URL'), '/');

        // 2. تنظيف الـ storage_key (مسار ملف master.m3u8 المخزن بالـ DB) من أي سكور زائد في أوله
        $videoPath = ltrim($this->storage_key, '/');

        // 3. دمجهم ليطلع رابط نظيف، سريع، ومحمي يمر عبر الـ CDN دغري
        return "{$workerUrl}/{$videoPath}";
    }
}
