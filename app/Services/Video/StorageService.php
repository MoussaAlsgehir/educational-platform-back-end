<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    protected $disk;

    public function __construct()
    {
        $this->disk = Storage::disk(config('video.cloud_disk'));
    }

    /**
     * رفع جميع ملفات HLS إلى التخزين السحابي مع مراقبة الأداء.
     */
    public function uploadHlsFolder(string $localFolder, string $cloudFolder): void
    {
        if (!File::isDirectory($localFolder)) {
            throw new \Exception("HLS folder does not exist.");
        }

        $files = File::allFiles($localFolder);
        $totalFiles = count($files);
        $totalSizeBytes = 0;
        $uploadedCount = 0;

        Log::info("[Storage] Starting upload to B2...", [
            'cloud_folder' => $cloudFolder,
            'total_files' => $totalFiles
        ]);

        $basePath = rtrim($localFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $startTime = microtime(true); // نبدأ بحساب الوقت

        foreach ($files as $file) {

            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($basePath)));
            $cloudPath = "{$cloudFolder}/{$relativePath}";

            $fileSize = $file->getSize();
            $totalSizeBytes += $fileSize;

            $stream = fopen($file->getRealPath(), 'rb');

            if ($stream === false) {
                throw new \Exception("Unable to open file: {$file->getFilename()}");
            }

            try {
                $this->disk->put($cloudPath, $stream, ['visibility' => 'private']);
                $uploadedCount++;

                // طباعة شريط تقدم بسيط باللوق
                Log::info("[Storage] Uploaded {$uploadedCount}/{$totalFiles} - {$file->getFilename()} (" . round($fileSize / 1024 / 1024, 2) . " MB)");

            } finally {
                fclose($stream);
            }
        }

        $endTime = microtime(true);
        $timeTaken = round($endTime - $startTime, 2);
        $totalSizeMB = round($totalSizeBytes / 1024 / 1024, 2);

        Log::info("[Storage] Upload completed successfully in {$timeTaken} seconds. Total size: {$totalSizeMB} MB");

        // فحص حجم البوكيت بعد كل رفع
        $this->logBucketSize();
    }

    /**
     * فحص وتسجيل الحجم الكلي للـ Bucket لتجنب تجاوز الحد المجاني.
     */
       public function logBucketSize(): void
    {
        try {
            $allFiles = $this->disk->allFiles();
            $totalSizeBytes = 0;

            foreach ($allFiles as $filePath) {
                try {
                    $totalSizeBytes += $this->disk->size($filePath);
                } catch (\Exception $e) {
                    // تجاهل الملفات المعطوبة وحساب حجمها
                    continue;
                }
            }

            $totalSizeMB = round($totalSizeBytes / 1024 / 1024, 2);
            $totalSizeGB = round($totalSizeBytes / 1024 / 1024 / 1024, 2);

            Log::info("[Storage] 📦 Current B2 Bucket Size: {$totalSizeMB} MB ({$totalSizeGB} GB)");

        } catch (\Exception $e) {
            Log::error("[Storage] Failed to calculate bucket size: " . $e->getMessage());
        }
    }

    /**
     * حذف الملفات المؤقتة بعد نجاح العملية.
     */
    public function cleanup(string $mp4Path, string $hlsFolder): void
    {
        if (File::exists($mp4Path)) {
            File::delete($mp4Path);
        }

        if (File::isDirectory($hlsFolder)) {
            File::deleteDirectory($hlsFolder);
        }

        Log::info("[Storage] Temporary files removed.");
    }
}
