<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CourseResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'course_type' => $this->course_type,
            'price' => $this->price,
            'category_names' => $this->WhenLoaded('categories',function () {
                return $this->categories->pluck('name');
            }),
            'status' => $this->status,
            'category_ids' => $this->WhenLoaded('categories',function () {
                return $this->categories->pluck('id');
            }),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'certificate_attendance_threshold' => $this->certificate_attendance_threshold,
            'cover_image' => $this->cover_image? asset(Storage::url($this->cover_image)) : null,
            'teacher' => new UserResource($this->teacher()->first()),
        ];
    }
}
