<?php

namespace App\Services;

use App\Models\Course;
use Carbon\Carbon;

class CourseStatusService
{
    
    public static function refresh(Course $course): Course
    {
        $now = Carbon::now();

        if ($course->status === 'pending') {

            if ($course->publish_type === 'live' && $course->start_date) {
                if ($now->gt(Carbon::parse($course->start_date))) {
                    $course->status = 'rejected';
                    $course->rejection_reason = 'Auto-rejected: Course start date passed without admin approval.';
                    $course->save();
                }
            }
            elseif ($course->publish_type === 'on_demand') {
                if ($now->gt($course->created_at->addDays(7))) {
                    $course->status = 'rejected';
                    $course->rejection_reason = 'Auto-rejected: 7 days passed without admin approval.';
                    $course->save();
                }
            }

            return $course;
        }

        if (in_array($course->status, ['hidden', 'rejected', 'draft']) || !$course->is_published) {
            return $course;
        }

        $startDate = $course->start_date ? Carbon::parse($course->start_date) : null;
        $endDate = $course->end_date ? Carbon::parse($course->end_date) : null;
        $newStatus = $course->status;

        if ($course->publish_type === 'live') {
            if ($startDate && $now->lt($startDate)) {
                $newStatus = 'upcoming';
            } elseif ($startDate && $now->gte($startDate) && (!$endDate || $now->lte($endDate))) {
                $newStatus = 'active';
            } elseif ($endDate && $now->gt($endDate)) {
                $newStatus = 'completed';
            }
        }
        else {
            $newStatus = 'active';
        }

        if ($course->status !== $newStatus) {
            $course->status = $newStatus;
            $course->save();
        }

        return $course;
    }
}
