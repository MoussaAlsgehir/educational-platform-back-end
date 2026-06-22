<?php

namespace App\Jobs;

use App\Models\LessonContent;
use App\Services\VideoProcessingService;
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
     * مهلة تشغيل الـ Job بالثواني (ساعة كاملة)
     */
    public $timeout = 3600;

    /**
     * عدد المحاولات لإعادة التشغيل في حال حدوث خطأ مؤقت في الشبكة مع Backblaze B2
     */
    public $tries = 3;

    /**
     * عدد الثواني للانتظار بين المحاولات
     */
    public $backoff = 30;

    protected LessonContent $lessonContent;
    protected string $mp4Path;

    public function __construct(LessonContent $lessonContent, string $mp4Path)
    {
        $this->lessonContent = $lessonContent;
        $this->mp4Path = $mp4Path;
    }

    public function handle()
    {
        Log::info("🏃‍♂️ [Job] تم بدء تشغيل الـ Job لمعالجة الفيديو رقم: {$this->lessonContent->id}");

        // تحديث الحالة إلى قيد المعالجة لضمان عدم التكرار
        $this->lessonContent->update(['status' => 'processing']);

        // استدعاء السيرفس وحقنها يدوياً بقلب الـ handle
        $processingService = app(VideoProcessingService::class);

        try {
            $processingService->convertMp4ToHls($this->lessonContent, $this->mp4Path);
        } catch (\Exception $e) {
            Log::error("❌ كراش بالـ Job أثناء المعالجة للسجل {$this->lessonContent->id}: " . $e->getMessage());

            // نحدث الحالة إلى فشل مبدئياً، ونرمي الاستثناء ليقوم الـ Queue بجدولته للمحاولة التالية
            $this->lessonContent->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * 🔥 الحصن المنيع: يتم استدعاؤها تلقائياً إذا فشل الـ Job تماماً بعد استنفاذ الـ 3 محاولات
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("🚨 [Job Failed] الـ Job مات نهائياً للسجل: {$this->lessonContent->id}. السبب: " . $exception->getMessage());

        // 1. التأكيد على حالة الفشل في قاعدة البيانات
        $this->lessonContent->update(['status' => 'failed']);

        // 2. التنظيف الفوري للهارد المحلي منعاً لتكدس الفيديوهات العالقة
        if (file_exists($this->mp4Path)) {
            unlink($this->mp4Path);
        }

        // مسح مجلد الـ HLS المؤقت التابع للسجل لتظل المساحة فارغة
        $hlsFolder = "hls_temp" . DIRECTORY_SEPARATOR . "lessonContent_{$this->lessonContent->id}";
        $localHlsPath = storage_path("app" . DIRECTORY_SEPARATOR . "private" . DIRECTORY_SEPARATOR . "{$hlsFolder}");

        if (file_exists($localHlsPath)) {
            $this->cleanLocalDirectory($localHlsPath);
        }

        Log::info("🧹 [Job Failed] تم تنظيف السيرفر المحلي بالكامل وحماية مساحة التخزين.");
    }

    /**
     * دالة مساعدة لتفريغ وحذف المجلد المؤقت في حالة الفشل النهائي
     */
    private function cleanLocalDirectory(string $dir): void
    {
        if (!file_exists($dir)) return;

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->cleanLocalDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
