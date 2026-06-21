<?php

namespace App\Http\Resources;

use App\Models\LessonContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'lesson_contents' => LessonContentResource::collection($this->whenLoaded('contents')),
            'order' => $this->order,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ] ;
    }
}
