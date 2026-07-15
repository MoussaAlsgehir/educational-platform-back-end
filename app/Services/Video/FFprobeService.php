<?php

namespace App\Services\Video;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FFprobeService
{
    protected string $ffprobePath;

    public function __construct()
    {
        $this->ffprobePath = config('video.ffprobe_path');
    }

    /**
     * التأكد من وجود ملف الفيديو.
     */
    public function validateFile(string $filePath): void
    {
        if (!File::exists($filePath) || !is_file($filePath)) {
            Log::error("[FFprobe] Video file not found: {$filePath}");

            throw new \Exception("Video file does not exist.");
        }
    }

    /**
     * جلب مدة الفيديو بالثواني.
     */
    public function getDuration(string $filePath): float
    {
        $this->validateFile($filePath);

        $process = new Process([
            $this->ffprobePath,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $filePath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("[FFprobe] Failed to read duration.", [
                'file' => $filePath,
                'error' => $process->getErrorOutput(),
            ]);

            throw new ProcessFailedException($process);
        }

        return (float) trim($process->getOutput());
    }

    /**
     * جلب معدل الإطارات الحقيقي.
     *
     * يحاول أولاً avg_frame_rate ثم يعود إلى r_frame_rate عند الحاجة.
     */
    public function getFrameRate(string $filePath): float
    {
        $this->validateFile($filePath);

        $fps = $this->readFrameRate($filePath, 'avg_frame_rate');

        if ($fps <= 0) {
            Log::warning("[FFprobe] avg_frame_rate unavailable. Falling back to r_frame_rate.");

            $fps = $this->readFrameRate($filePath, 'r_frame_rate');
        }

        if ($fps <= 0) {
            throw new \Exception("Unable to determine video frame rate.");
        }

        return $fps;
    }

    /**
     * قراءة قيمة Frame Rate من FFprobe.
     */
    private function readFrameRate(string $filePath, string $entry): float
    {
        $process = new Process([
            $this->ffprobePath,
            '-v',
            'error',
            '-select_streams',
            'v:0',
            '-show_entries',
            "stream={$entry}",
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $filePath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            return 0.0;
        }

        $output = trim($process->getOutput());

        if ($output === '' || !str_contains($output, '/')) {
            return 0.0;
        }

        [$numerator, $denominator] = explode('/', $output);

        $numerator = (float) $numerator;
        $denominator = (float) $denominator;

        if ($denominator <= 0) {
            return 0.0;
        }

        return $numerator / $denominator;
    }
        /**
     * جلب ارتفاع الفيديو (Height) لفحص الدقة
     */
    public function getHeight(string $filePath): int
    {
        $this->validateFile($filePath);

        $process = new Process([
            $this->ffprobePath,
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=height',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $filePath,
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return (int) trim($process->getOutput());
    }
}
