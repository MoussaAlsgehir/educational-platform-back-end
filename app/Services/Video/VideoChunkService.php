<?php

namespace App\Services\Video;

use App\Models\Lesson;
use App\Models\VideoChunk;
use App\Jobs\ProcessVideoHlsJob;
use App\Models\LessonContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VideoChunkService
{
    /**
     * جلب أرقام القطع المرفوعة مسبقاً للجلسة
     */
    public function getUploadedChunks(string $sessionId): array
    {
        return VideoChunk::where('upload_session_id', $sessionId)
            ->orderBy('chunk_index', 'asc')
            ->pluck('chunk_index')
            ->toArray();
    }

    /**
     * معالجة القطعة المرفوعة وتخزينها، وتجميعها إن اكتملت
     */
    public function handleChunk(string $sessionId, int $chunkIndex, int $totalChunks, bool $is_full_hd, UploadedFile $file, Lesson $lesson): ?LessonContent
    {
        // 1. تخزين القطعة محلياً
        $temporaryFolder = "chunks/{$sessionId}";
        $fileName = "chunk_{$chunkIndex}.part";
        $path = $file->storeAs($temporaryFolder, $fileName, 'local');

        // 2. تسجيلها بدفتر الحسابات
        VideoChunk::updateOrCreate(
            ['upload_session_id' => $sessionId, 'chunk_index' => $chunkIndex],
            ['lesson_id' => $lesson->id, 'total_chunks' => $totalChunks, 'temporary_path' => $path]
        );

        // 3. تشييك الاكتمال والتجميع
        $uploadedChunksCount = VideoChunk::where('upload_session_id', $sessionId)->count();

        if ($uploadedChunksCount === $totalChunks) {

            // دمج القطع
            $finalVideoLocalPath = $this->mergeChunks($sessionId, $lesson);

            // حساب الترتيب وإنشاء المحتوى بجدول الـ lesson_contents
            $nextOrder = ($lesson->contents()->max('order') ?? 0) + 1;

            $lessonContent = $lesson->contents()->create([
                'type'        => 'video',
                'title'       => $lesson->title . ' - الشرح الأساسي',
                'status'      => 'processing',
                'storage_key' => null,
                'order'       => $nextOrder,
                'is_full_hd'  => $is_full_hd,
            ]);


             dispatch(new ProcessVideoHlsJob($lessonContent, $finalVideoLocalPath));


            return $lessonContent;
        }

        return null; // الرفع مستمر ولم يكتمل بعد
    }

    /**
     * خوارزمية الدمج وضخ الـ Streams
     */
    private function mergeChunks(string $sessionId, Lesson $lesson): string
    {
        $chunks = VideoChunk::where('upload_session_id', $sessionId)
            ->orderBy('chunk_index', 'asc')
            ->get();

        $finalFileName = "lesson_{$lesson->id}_" . time() . ".mp4";
        $finalDirectory = storage_path("app/private/videos_temp");

        if (!file_exists($finalDirectory)) {
            mkdir($finalDirectory, 0755, true);
        }

        $finalPath = "{$finalDirectory}/{$finalFileName}";
        $outputFile = fopen($finalPath, 'ab');

        foreach ($chunks as $chunk) {
            $chunkFullPath = storage_path("app/private/" . $chunk->temporary_path);

            $inputFile = fopen($chunkFullPath, 'rb');
            stream_copy_to_stream($inputFile, $outputFile);
            fclose($inputFile);

            unlink($chunkFullPath);
        }

        fclose($outputFile);

        Storage::disk('local')->deleteDirectory("chunks/{$sessionId}");
        VideoChunk::where('upload_session_id', $sessionId)->delete();

        return $finalPath;
    }
}
