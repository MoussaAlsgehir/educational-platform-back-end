<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 1. نفحص إذا الطالب الحالي مشترك بالكورس (إذا كان مسجل دخول)
               $isEnrolled = false;
        $user = $request->user('sanctum');

        $originalPrice = (float) $this->price;
        $finalPrice = $this->getFinalPrice();
        $discountPercentage = $finalPrice < $originalPrice ? (float) $this->activeDiscount->percentage : 0;
        if ($user) {
            if ($user->isAdmin() || $this->teacher_id === $user->id) {
                $isEnrolled = true;
            } else {
                $isEnrolled = $this->students()->where('student_id', $user->id)->exists();

            }


        }




        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'course_type' => $this->course_type, // quiz_based, attendance_only
            'publish_type' => $this->publish_type, // live, on_demand
            'navigation_type' => $this->navigation_type, // free, sequential
            'price' => $this->price,
            'status' => $this->status, // upcoming, active, completed...
            'is_published' => $this->is_published,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'certificate_attendance_threshold' => $this->certificate_attendance_threshold,
            'cover_image' => $this->cover_image ? asset(Storage::url($this->cover_image)) : null,
            'is_enrolled' => $isEnrolled,
             'pricing' => [
                'original_price' => $originalPrice,
                'discount_percentage' => $discountPercentage, // 0 إذا ما في خصم
                'final_price' => round($finalPrice, 2), // السعر بعد الخصم
                'has_active_discount' => $discountPercentage > 0, // True/False لعرض شارة "تخفيض"
            ],
                        //  عدد الطلاب المسجلين (بيعتمد على withCount('students') بالكونترولر)
            'enrolled_students_count' => $this->whenCounted('students', $this->students_count, 0),
            'total_minutes' =>$this->getTotalMinutes(), 

            'rating' => [
                'average' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 1) : 0,
                'count' => $this->reviews_count ?? 0,
            ],


            'interaction_indicators' => [
                'chat_activity' => 'normal',   // slow, normal,fast
                'response_speed' => 'slow',   //slow, normal, fast
            ],


            'teacher' => new UserResource($this->whenLoaded('teacher')),
            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->pluck('name');
            }),

                     // المرفقات العامة للكورس
            'attachments' => $this->whenLoaded('attachments', function () use ($isEnrolled) {
                return $this->attachments->whereNull('section_id')->map(function ($attachment) use ($isEnrolled) {
                    $url = null;

                    //  رابط المرفق يظهر بس للمشتركين
                    if ($isEnrolled) {
                        $url = $attachment->file_url;
                        if ($attachment->type !== 'link' && $url && !filter_var($url, FILTER_VALIDATE_URL)) {
                            $url = rtrim(env('CLOUDFLARE_WORKER_URL'), '/') . '/' . $url;
                        }
                    }

                    return [
                        'id' => $attachment->id,
                        'title' => $attachment->title,
                        'type' => $attachment->type,
                        'is_locked' => !$isEnrolled,
                        'url' => $url,
                    ];
                })->values();
            }),


            'sections' => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
