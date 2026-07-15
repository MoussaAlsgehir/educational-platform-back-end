<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\LessonContent;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{
    public function generateLink($contentId)
    {
        // 1. نجيب الفيديو
        $content = LessonContent::findOrFail($contentId);

        // 2. نتأكد إنه فيديو وجاهز للتحميل
        if ($content->type !== 'video' || $content->status !== 'ready') {
            return ApiResource::sendResponse("Video not ready for download.", null, 400);
        }

        // 3. (مهم) تحقق من اشتراك الطالب في الكورس
        // ملاحظة هون الكود يلي يفحص إذا الطالب مشترك أو لأ
        // Gate::authorize('access-lesson', $content);

        // 4. إعداد بيانات التوكن (صالح لساعتين)
        $secret = env('HLS_SHARED_SECRET');
        $expires = time() + (2 * 60 * 60); // ساعتين بالثواني
        $userId = Auth::id();
        $contentId = $content->id;

        // 5. بناء التوقيع (HMAC)
        $data = "{$contentId}:{$userId}:{$expires}";
        $signature = hash_hmac('sha256', $data, $secret);

        // 6. دمج البيانات لتشكيل التوكن النهائي
        $token = base64_encode("{$data}:{$signature}");

        // 7. بناء الرابط النهائي
        $workerUrl = rtrim(env('CLOUDFLARE_WORKER_URL'), '/');
        $downloadUrl = "{$workerUrl}/{$content->storage_key}?token=" . urlencode($token);

        return ApiResource::sendResponse("Download link generated successfully.", [
            'download_url' => $downloadUrl,
            'expires_in' => '2 hours'
        ]);
    }
}
