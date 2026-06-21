<?php

namespace App\Services;

use App\Models\LessonContent;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoProcessingService
{
    /**
     * معالجة الفيديو وتحويله لـ HLS وحساب المدة والرفع لـ Backblaze B2
     */
    public function convertMp4ToHls(LessonContent $lessonContent, string $mp4Path): void
    {
        Log::info("⚙️ [Service] بدأنا معالجة الفيديو للسجل: {$lessonContent->id}");

        $cores = 2;
        if(stristr( PHP_OS, 'WIN')) {
            // Windows
            $cores =(int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        }
        else{
            // Linux
            $cores =(int) trim(shell_exec('nproc'));
        }

        $threads = max(1, $cores - 1);

        Log::info("⚙️ [Service] عدد السحابات المستخدمة: {$threads}");

        // 3. تجهيز الفولدر المحلي (رفعناه للأعلى لنفحص قبل تشغيل الـ FFmpeg المكلف)
        $hlsFolder = "hls_temp" . DIRECTORY_SEPARATOR . "lessonContent_{$lessonContent->id}";
        $localHlsPath = storage_path("app" . DIRECTORY_SEPARATOR . "private" . DIRECTORY_SEPARATOR . "{$hlsFolder}");

        //  (Idempotency): إذا تم إعادة الـ Job وكان التقطيع جاهزاً محلياً، لا تفرمه مجدداً
        if (file_exists("{$localHlsPath}/master.m3u8")) {
            Log::info("⏭️ [Service] ملف الـ HLS موجود محلياً مسبقاً. تخطي مرحلة الـ FFmpeg والانتقال للرفع مباشرة.");
        } else {
            if (!file_exists($localHlsPath)) {
                mkdir($localHlsPath, 0755, true);
            }

            // 1. إعداد الـ FFmpeg على الويندوز
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => 'C:/ffmpeg/bin/ffmpeg.exe',
                'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe',
                'timeout'          => 3600,
                'ffmpeg.threads'   => $threads,
            ]);

            // 2. فتح ملف الـ mp4
            $video = $ffmpeg->open($mp4Path);

            // 4. الفرم لـ HLS
            $format = new X264();
            $format->setAudioCodec('aac');

            $video->save($format, "{$localHlsPath}/master.m3u8", [
                '-profile:v', 'baseline',
                '-level', '3.0',
                '-start_number', '0',
                '-hls_time', '6',
                '-hls_list_size', '0',
                '-f', 'hls'
            ]);
        }

        // 5. حساب مدة الفيديو عبر FFProbe
        $ffprobe = FFProbe::create([
            'ffprobe.binaries' => 'C:/ffmpeg/bin/ffprobe.exe'
        ]);
        $duration = (int) $ffprobe->format($mp4Path)->get('duration');

        // 6. [الرفع السحابي الفعلي إلى Backblaze B2]
        Log::info("☁️ [Service] جاري رفع ملفات الـ HLS إلى سحابة Backblaze B2...");
        $cloudFolder = "courses/videos/lesson_{$lessonContent->lesson_id}/content_{$lessonContent->id}";

        $files = scandir($localHlsPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fileLocalPath = $localHlsPath . DIRECTORY_SEPARATOR . $file;
            $cloudKey = $cloudFolder . '/' . $file;

            // 🛡️ فحص ذكي: إذا كان الملف مرفوعاً مسبقاً في محاولة فاشلة، تخطاه ووفر البيانات والوقت
            if (Storage::disk('b2')->exists($cloudKey)) {
                continue;
            }

            // الرفع الفعلي عبر Stream لضمان أداء خارق وأقل استهلاك للرام
            $stream = fopen($fileLocalPath, 'r');
            Storage::disk('b2')->put($cloudKey, $stream);

            //  تفصيل هندسي: إغلاق الـ Stream فوراً لتحرير الرام ومنع تعليق الذاكرة
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $realCloudKey = "{$cloudFolder}/master.m3u8";
        // 7. تحديث السجل بقاعدة البيانات بالمسار السحابي الفعلي الجاهز
        $lessonContent->update([
            'status'      => 'ready',
            'storage_key' => $realCloudKey,
            'duration'    => $duration,
        ]);

        // 8. التنظيف الفيزيائي للهارد بعد النجاح التام
        if (file_exists($mp4Path)) {
            unlink($mp4Path);
        }

        // تفريغ المجلد المحلي وحذفه بالكامل ليبقى سيرفرك نظيفاً 100%
        $this->deleteLocalDirectory($localHlsPath);

        Log::info("✅ [Service] انتهت العملية بنجاح وتم تنظيف السيرفر السحابي والمحلي.");
    }

    /**
     * دالة مساعدة لمسح المجلد بجميع محتوياته فوراً
     */
    private function deleteLocalDirectory(string $dir): void
    {
        if (!file_exists($dir)) return;

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteLocalDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
