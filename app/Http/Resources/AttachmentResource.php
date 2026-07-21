<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

           {
        $url =$this->file_url;

        // إذا كان المرفق ملف (مو رابط خارجي)، نركب له رابط الـ Worker
        if ($this->type !== 'link' && $url && !filter_var($url, FILTER_VALIDATE_URL)) {
            $workerUrl = rtrim(env('CLOUDFLARE_WORKER_URL'), '/');
            $url = "{$workerUrl}/{$url}";
        }

        return [
            'id' =>$this->id,
            'course_id' =>$this->course_id,
            'section_id' =>$this->section_id,
            'title' =>$this->title,
            'type' =>$this->type,
            'url' => $url,
            'created_at' =>$this->created_at->format('Y-m-d H:i:s'),
        ];
    }
    }
}
