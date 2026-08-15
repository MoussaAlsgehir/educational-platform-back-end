<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\InstructorRequest;
use App\Models\Role;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;

class AdminInstructorRequestController extends Controller
{
    // عرض الطلبات المعلقة
    public function pending()
    {
        $requests = InstructorRequest::where('status', 'pending')->with('user')->latest()->get();
        return ApiResource::sendResponse("Pending instructor requests.", $requests);
    }

    // المراجعة (موافقة أو رفض) في Endpoint واحد
    public function review(Request $request, InstructorRequest $instructorRequest)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string'
        ]);

        if ($instructorRequest->status !== 'pending') {
            return ApiResource::sendResponse("This request has already been processed.", null, 400);
        }

        // تحديث حالة الطلب
        $instructorRequest->status = $request->status;
        $instructorRequest->rejection_reason = $request->rejection_reason;
        $instructorRequest->save();

        // إذا تمت الموافقة، نعطي المستخدم دور "مدرس"
        if ($request->status === 'approved') {
            $instructorRole = Role::where('name', 'instructor')->first();
            if ($instructorRole) {
                // syncWithoutDetaching عشان ما نحذف دور الطالب، بس نضيف دور المدرس
                $instructorRequest->user->roles()->syncWithoutDetaching([$instructorRole->id]);
            }
        }
                if ($request->status === 'approved') {
            $instructorRequest->user->notify(new GeneralNotification(
                "Congratulations! You are now an Approved Instructor",
                "Your request to become an instructor on LearNova has been approved.",
                "instructor_approved"
            ));
        } else {
            $instructorRequest->user->notify(new GeneralNotification(
                "Instructor Request Rejected",
                "We are sorry to inform you that your instructor request was rejected. Reason: {$request->rejection_reason}",
                "instructor_rejected"
            ));
        }

        return ApiResource::sendResponse("Request {$request->status} successfully.", $instructorRequest);
    }

        // استعراض كل طلبات الترقية (مع فلترة اختيارية للحالة)
    public function index(Request $request)
    {
        $query = InstructorRequest::with('user')->latest();

        // إذا بعتوا status=pending بتجيب المعلقة بس، وهكذا
        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(10);

        return ApiResource::sendResponse("Instructor requests retrieved.", [
            'requests' => $requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ]
        ]);
    }
}
