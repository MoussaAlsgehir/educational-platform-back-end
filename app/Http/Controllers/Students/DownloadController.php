<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\LessonContent;
use Gate;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{
    public function generateLink($contentId)
    {
        // 1. نجيب الفيديو مع الدرس والقسم والكورس (عبر العلاقات)
        $content = LessonContent::with('lesson.section.course')->findOrFail($contentId);

        // 2. نتأكد إنه فيديو وجاهز
        if ($content->type !== 'video' || $content->status !== 'ready') {
            return ApiResource::sendResponse("Video not ready for download.", null, 400);
        }

        $user = Auth::user();
        $course = $content->lesson->section->course;


        Gate::authorize('viewContent', $course);

        // 4. إعدادات التوكن (صالح لساعتين)
        $secret = env('HLS_SHARED_SECRET');
        $expires = time() + (2 * 60 * 60);
        $userId = $user->id;
        $contentId = $content->id;

        // 5. بناء التوقيع (HMAC)
        $data = "{$contentId}:{$userId}:{$expires}";
        $signature = hash_hmac('sha256', $data, $secret);
        $token = base64_encode("{$data}:{$signature}");

        // 6. بناء الرابط النهائي
        $workerUrl = rtrim(env('CLOUDFLARE_WORKER_URL'), '/');
        $downloadUrl = "{$workerUrl}/{$content->storage_key}?token=" . urlencode($token);

        return ApiResource::sendResponse("Download link generated successfully.", [
            'download_url' => $downloadUrl,
            'expires_in' => '2 hours'
        ]);
    }
}
