<?php

namespace App\Http\Controllers\Platform_learnova; // تعديل الـ namespace ليتطابق مع المجلد الجديد

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * جلب جميع إشعارات المستخدم الحالي مقسمة إلى صفحات (Paginated)
     * مع فلترة حقول الاستجابة لحماية الذاكرة وعزل تفاصيل النظام الداخلية.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $unreadCount = $user->unreadNotifications()->count();
        $paginator = $user->notifications()->latest()->paginate(15);

        $data = [
            'unread_count'  => $unreadCount,
            'notifications' => NotificationResource::collection($paginator->items()),
            'pagination'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'has_more'     => $paginator->hasMorePages(),
            ]
        ];

        return ApiResource::sendResponse("Notifications retrieved successfully", $data);
    }

    /**
     * تحديث حالة جميع الإشعارات غير المقروءة الخاصة بالمستخدم إلى "مقروءة"
     * عبر استعلام مباشر وسريع (Single SQL Query) دون تحميل السجلات في الذاكرة.
     */
    public function markAsRead()
    {
        /** @var User $user */
        $user = Auth::user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return ApiResource::sendResponse("All notifications marked as read");
    }
}
