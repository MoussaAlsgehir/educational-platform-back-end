<?php

namespace App\Jobs;

use App\Models\LessonContent;
use App\Services\Video\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * مهلة تشغيل الـ Job بالثواني
     */
    public int $timeout = 3600;

    /**
     * عدد المحاولات في حال الفشل المؤقت
     */
    public int $tries = 3;

    /**
     * وقت الانتظار بين المحاولات
     */
    public int $backoff = 30;

    protected int $lessonContentId;
    protected string $mp4Path;
    protected LessonContent $lessonContent;


    public function __construct(LessonContent $lessonContent, string $mp4Path)
    {
        $this->lessonContentId = $lessonContent->id;
        $this->lessonContent = $lessonContent;
        $this->mp4Path = $mp4Path;
    }


    /**
     * تنفيذ معالجة الفيديو
     */
    public function handle(VideoProcessingService $processingService): void
    {
        $lessonContent = LessonContent::findOrFail($this->lessonContentId);

        Log::info(
            "🏃 [Video Job] بدء معالجة الفيديو رقم: {$lessonContent->id}"
        );

        try {

            // منع أي تكرار في حالة إعادة تشغيل الـ Job
            $lessonContent->update([
                'status' => 'processing'
            ]);


            $processingService->convertMp4ToHls(
                $lessonContent,
                $this->mp4Path
            );


            Log::info(
                "✅ [Video Job] انتهت معالجة الفيديو بنجاح: {$lessonContent->id}"
            );


        } catch (\Throwable $e) {

            Log::error(
                "❌ [Video Job] فشل معالجة الفيديو {$lessonContent->id}: "
                . $e->getMessage()
            );

            // لا نضع failed هنا
            // Laravel سيعيد المحاولة حسب tries
            throw $e;
        }
    }


    /**
     * يتم استدعاؤها فقط بعد فشل جميع المحاولات
     */
public function failed(\Throwable $exception): void
{
    Log::error("[Job Failed] Video processing failed", [
        'lesson_content_id' => $this->lessonContentId,
        'error' => $exception->getMessage(),
    ]);

    // 1. تحديث الداتابيز إنه فشل
    $this->lessonContent->update(['status' => 'failed']);

    // 2. حذف الفيديو الأصلي (MP4) إذا موجود
    if (isset($this->mp4Path) && file_exists($this->mp4Path)) {
        unlink($this->mp4Path);
        Log::warning("[Cleanup] Deleted original MP4 due to failure: {$this->mp4Path}");
    }

    // 3. حذف مجلد الـ HLS المؤقت إذا كان انشأ قبل ما يفشل
    $hlsFolder = storage_path("app/private/hls_temp/lessonContent_{$this->lessonContentId}");
    if (is_dir($hlsFolder)) {
        $this->deleteDirectory($hlsFolder); // أنت عندك دالة deleteDirectory جاهزة بنفس الملف
        Log::warning("[Cleanup] Deleted HLS temp folder due to failure: {$hlsFolder}");
    }
}

    /**
     * حذف فولدر كامل مع محتوياته
     */
    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }


        $items = scandir($directory);


        foreach ($items as $item) {

            if ($item === '.' || $item === '..') {
                continue;
            }


            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;


            if (is_dir($path)) {

                $this->deleteDirectory($path);

            } else {

                unlink($path);
            }
        }


        rmdir($directory);
    }
}
