<?php

namespace App\Services\Video;

use App\Models\LessonContent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class VideoProcessingService
{
    protected FFprobeService $ffprobeService;
    protected StorageService $storageService;

    protected string $ffmpegPath;

    public function __construct(
        FFprobeService $ffprobeService,
        StorageService $storageService
    ) {
        $this->ffprobeService = $ffprobeService;
        $this->storageService = $storageService;

        $this->ffmpegPath = config('video.ffmpeg_path');
    }

    /**
     * تحويل ملف MP4 إلى HLS ورفعه للتخزين السحابي.
     */
    public function convertMp4ToHls(
        LessonContent $lessonContent,
        string $mp4Path
    ): void {

        Log::info("[Video] Starting processing", [
            'lesson_content_id' => $lessonContent->id,
        ]);

        // 1. التأكد من وجود الملف وصلاحيته
        $this->ffprobeService->validateFile($mp4Path);

        // 2. إعداد المسار المحلي المؤقت
        $localHlsPath = storage_path("app/private/hls_temp/lessonContent_{$lessonContent->id}");
        File::ensureDirectoryExists($localHlsPath);

        $playlistName = "master.m3u8";
        $playlistPath = $localHlsPath . DIRECTORY_SEPARATOR . $playlistName;

        // 3. قراءة معلومات الفيديو الأساسية
        $fps = $this->ffprobeService->getFrameRate($mp4Path);
        $duration = $this->ffprobeService->getDuration($mp4Path);
                $fps = $this->ffprobeService->getFrameRate($mp4Path);
        $duration = $this->ffprobeService->getDuration($mp4Path);

        //  فحص الدقة: نمنع أي فيديو أقل من 720p
              //  فحص الدقة الأساسية
        $height = $this->ffprobeService->getHeight($mp4Path);
        if ($height < 720) {
            throw new \Exception("Video resolution must be at least 720p. Current height: {$height}px");
        }

        // 🔥 إجبار 1080p: إذا اختارها المدرس، الفيديو يجب أن يكون 1080p أو أعلى
        if ($lessonContent->is_full_hd && $height < 1080) {
            throw new \Exception("You selected Full HD (1080p), but the uploaded video is only {$height}px. Please upload a 1080p video or uncheck the Full HD option.");
        }

        // هنا بنحدد هل نولد 1080p أو لا (بما إننا فحصنا فوق، فهي بتكون true فقط إذا كان الفيديو 1080p فعلاً)
        $generate1080p = $lessonContent->is_full_hd;
        Log::info("[Video] Video information", [
            'fps' => $fps,
            'duration' => $duration,
            'height' => $height,
        ]);


        // 4. توليد ملفات HLS (إذا لم تكن موجودة مسبقاً)
        if (!File::exists($playlistPath)) {
            $command = $this->buildHlsCommand(
                $mp4Path,
                $localHlsPath,
                $playlistName,
                $fps,
                $lessonContent->is_full_hd
            );

            $process = new Process($command);
            $process->setTimeout(config('video.timeout', 3600));

            Log::info("[Video] Running FFmpeg: " . $process->getCommandLine());

            // mustRun بترمي Exception لو فيه خطأ بالتنفيذ
            $process->mustRun();

            Log::info("[Video] FFmpeg finished successfully");
        }

        // 5. التحقق من نجاح التوليد
        $this->validateHlsOutput($localHlsPath, $playlistPath);


        //  تعديل ملف الـ Master Playlist ليدعم Audio Only بشكل قياسي
        $this->formatMasterPlaylistForAudioOnly($playlistPath);

        // 6. المسار السحابي...

        // 6. المسار السحابي (مثلاً: videos/lessonContent_123/index.m3u8)
        $cloudFolder = "videos/lessonContent_{$lessonContent->id}";
        $cloudStorageKey = "{$cloudFolder}/{$playlistName}";

        // 7. رفع المجلد كاملاً للـ Backblaze B2
        $this->storageService->uploadHlsFolder($localHlsPath, $cloudFolder);

        // 8. تحديث الداتابيز وحذف الملفات المؤقتة
        $lessonContent->update([
            'status'      => 'ready',
            'duration'    => $duration,
            'storage_key' => $cloudStorageKey, // الحفظ النهائي للمسار
        ]);

        // 9. تنظيف السيرفر من ملفات MP4 و HLS المؤقتة
        $this->storageService->cleanup($mp4Path, $localHlsPath);

        Log::info("[Video] Processing completed and uploaded  successfully ", [
           // 'storage_key' => $cloudStorageKey,
        ]);
    }

    /**
     * بناء أمر FFmpeg الخاص بتحويل HLS.
     * تم تزبيط الإعدادات لتعمل كـ VOD وتدعم Seeking بشكل ممتاز.
     */    /**
     * بناء أمر FFmpeg لتحويل الفيديو إلى HLS متعدد الدقات (Adaptive Bitrate).
     */
    private function buildHlsCommand(
        string $inputPath,
        string $outputFolder,
        string $playlistName,
        float $fps,
        bool $isFullHd = false
    ): array {

        $segmentDuration = config('video.defaults.segment_duration', 6);

        // بناء الـ GOP
        $gopSize = (int) round($fps * $segmentDuration);

        $command = [
            $this->ffmpegPath,
            '-y',
            '-i', $inputPath,

            // إعدادات الفيديو الأساسية
            '-c:v', 'libx264',
            '-profile:v', 'high',
            '-preset', 'fast',
            '-g', (string) $gopSize,
            '-keyint_min', (string) $gopSize,
            '-sc_threshold', '0',

            // إعدادات الصوت
            '-c:a', 'aac',
            '-b:a', '128k',
            '-ac', '2',
            '-ar', '48000',

            // 1. الدقة الأولى: 360p (Bitrate 800k)
            '-s:v:0', '640x360',
            '-b:v:0', '800k',

            // 2. الدقة الثانية: 480p (Bitrate 1400k)
            '-s:v:1', '854x480',
            '-b:v:1', '1400k',

            // 3. الدقة الثالثة: 720p (Bitrate 2800k)
            '-s:v:2', '1280x720',
            '-b:v:2', '2800k',
        ];

        // إعداد الـ Maps (نخبر FFmpeg يجهز نسخ من الفيديو والصوت)
        // كل فيديو محتاج Map، وكل صوت محتاج Map
        // رح نجهز: 3 فيديوهات + 4 أصوات (3 للفيديوهات + 1 للصوت المنفرد Audio-Only)
        $command = array_merge($command, [
            '-map', '0:v:0', '-map', '0:a:0', // للـ 360p
            '-map', '0:v:0', '-map', '0:a:0', // للـ 480p
            '-map', '0:v:0', '-map', '0:a:0', // للـ 720p
            '-map', '0:a:0',                  // للـ Audio-Only Track
        ]);

        // إذا كان الفيديو يدعم 1080p (is_full_hd = true)
        if ($isFullHd) {
            $command[] = '-s:v:3';
            $command[] = '1920x1080';
            $command[] = '-b:v:3';
            $command[] = '5000k';

            // نضيف Map للـ 1080p
            $command[] = '-map';
            $command[] = '0:v:0';
            $command[] = '-map';
            $command[] = '0:a:0';

            // var_stream_map: تخبر FFmpeg كيف يدمج الفيديو مع الصوت في ملف الـ master
            // v:0,a:0 (360p) | v:1,a:1 (480p) | v:2,a:2 (720p) | v:3,a:3 (1080p) | a:4 (Audio Only)
            $command[] = '-var_stream_map';
            $command[] = 'v:0,a:0 v:1,a:1 v:2,a:2 v:3,a:3 a:4';
        } else {
            // var_stream_map بدون 1080p
            // v:0,a:0 (360p) | v:1,a:1 (480p) | v:2,a:2 (720p) | a:3 (Audio Only)
            $command[] = '-var_stream_map';
            $command[] = 'v:0,a:0 v:1,a:1 v:2,a:2 a:3';
        }

        // إعدادات HLS
        $command = array_merge($command, [
            '-f', 'hls',
            '-hls_time', (string) $segmentDuration,
            '-hls_list_size', '0',
            '-hls_playlist_type', 'vod',
            '-hls_flags', 'independent_segments',
            '-hls_segment_type', 'mpegts',

            // تسمية ملفات الـ TS. %v تعني رقم الـ Variant (0 للـ 360، 1 للـ 480، الخ)
            '-hls_segment_filename', $outputFolder . DIRECTORY_SEPARATOR . 'stream_%v_seg_%03d.ts',

            // اسم ملف الـ Master Playlist
            '-master_pl_name', $playlistName,

            // Output pattern لكل صيغة (بينشئ ملف stream_0.m3u8، stream_1.m3u8، الخ)
            $outputFolder . DIRECTORY_SEPARATOR . 'stream_%v.m3u8',
        ]);

        return $command;
    }

    /**
     * التأكد من أن FFmpeg أنتج ملفات HLS سليمة.
     */
     /**
     * التأكد من أن FFmpeg أنتج ملفات HLS سليمة.
     */
    private function validateHlsOutput(string $folder, string $playlistPath): void
    {
        if (!File::exists($playlistPath)) {
            throw new \Exception("HLS Master Playlist (master.m3u8) was not generated.");
        }

        $segments = collect(File::files($folder))->filter(function ($file) {
            return $file->getExtension() === 'ts';
        });

        if ($segments->count() === 0) {
            throw new \Exception("No HLS segments (.ts) were generated.");
        }
    }
        /**
     * تعديل ملف master.m3u8 ليتوافق مع معايير hls.js
     * يبحث ديناميكياً عن مسار الصوت المستقل سواء كان stream_3 أو stream_4
     */
    private function formatMasterPlaylistForAudioOnly(string $playlistPath): void
    {
        if (!file_exists($playlistPath)) {
            return;
        }

        $content = file_get_contents($playlistPath);
        $lines = explode("\n", $content);
        $newLines = [];
        $audioUri = null;
        $skipNextUri = false;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // 1. تحديد سطر الـ STREAM-INF الخاص بالصوت (الذي لا يملك RESOLUTION)
            if (strpos($line, '#EXT-X-STREAM-INF:') === 0 && strpos($line, 'RESOLUTION=') === false) {
                $skipNextUri = true; // السطر الذي يليه هو رابط ملف الصوت (stream_3 أو stream_4)
                continue;
            }

            // 2. التقاط رابط ملف الصوت وتجاهله (عدم كتابته بالملف الجديد)
            if ($skipNextUri) {
                $audioUri = $line; // حفظ اسم الملف (مثلاً: stream_4.m3u8)
                $skipNextUri = false;
                continue;
            }

            // 3. إضافة AUDIO="audio_only" لأسطر الفيديوهات فقط
            if (strpos($line, '#EXT-X-STREAM-INF:') === 0 && strpos($line, 'RESOLUTION=') !== false) {
                $line = rtrim($line, ',') . ',AUDIO="audio_only"';
            }

            $newLines[] = $line;
        }

        // 4. إذا وجدنا مسار صوتي، نقوم بإعادة بناء الملف وإضافة التاج القياسي
        if ($audioUri) {
            $audioMediaTag = '#EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID="audio_only",NAME="Audio Only",DEFAULT=NO,AUTOSELECT=NO,URI="' . $audioUri . '"';

            $finalContent = implode("\n", $newLines);

            // إضافة تاج الـ Audio بعد سطر #EXT-X-VERSION مباشرة
            $finalContent = preg_replace(
                '/(#EXT-X-VERSION:\d+)/',
                "$1\n" . $audioMediaTag,
                $finalContent,
                1
            );

            // حفظ الملف من جديد
            file_put_contents($playlistPath, $finalContent);

            Log::info("[Video] master.m3u8 formatted successfully. Audio track linked: {$audioUri}");
        }
    }
}
