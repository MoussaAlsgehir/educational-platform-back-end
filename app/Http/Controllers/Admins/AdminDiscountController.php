<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\CourseDiscount;
use Illuminate\Http\Request;

class AdminDiscountController extends Controller
{
    // استعراض كل طلبات الخصم (مع فلترة اختيارية للحالة)
    public function index(Request $request)
    {
        $query = CourseDiscount::with('course.teacher:id,first_name,last_name')->latest();

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $discounts = $query->paginate(10);

        return ApiResource::sendResponse("Discount requests retrieved.", [
            'discounts' => $discounts->items(),
            'pagination' => [
                'current_page' => $discounts->currentPage(),
                'last_page'    => $discounts->lastPage(),
                'total'        => $discounts->total(),
            ]
        ]);
    }

    // مراجعة طلب خصم (قبول أو رفض)
    public function review(Request $request, CourseDiscount $discount)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        if ($discount->status !== 'pending') {
            return ApiResource::sendResponse("This request has already been processed.", null, 400);
        }

        $discount->status = $request->status;
        $discount->rejection_reason = $request->rejection_reason;
        $discount->save();

        return ApiResource::sendResponse("Discount request {$request->status} successfully.", $discount);
    }
}
