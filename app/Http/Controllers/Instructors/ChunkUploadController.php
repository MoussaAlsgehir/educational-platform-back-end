<?php

namespace App\Http\Controllers\Instructors;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\VideoChunkService;
use App\Helpers\ApiResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ChunkUploadController extends Controller
{
    protected VideoChunkService $chunkService;

    // حقن السيرفس تلقائياً
    public function __construct(VideoChunkService $chunkService)
    {
        $this->chunkService = $chunkService;
    }

    /**
     * فحص حالة الرفع والاستكمال
     */
    public function checkProgress(Request $request, int $lessonId)
    {
        $request->validate(['upload_session_id' => 'required|string']);
        $lesson = Lesson::findOrFail($lessonId);


        $uploadedChunks = $this->chunkService->getUploadedChunks($request->upload_session_id);

        return ApiResource::sendResponse("Progress retrieved.", [
            'uploaded_chunks' => $uploadedChunks,
            'next_expected_chunk' => count($uploadedChunks) > 0 ? max($uploadedChunks) + 1 : 0
        ], 200);
    }

    /**
     * استقبال القطعة
     */
    public function uploadChunk(Request $request, int $lessonId)
    {
        $request->validate([
            'upload_session_id' => 'required|string',
            'chunk_index'       => 'required|integer|min:0',
            'total_chunks'      => 'required|integer|min:1',
            'file'              => 'required|file|max:15360',
        ]);

        $lesson = Lesson::findOrFail($lessonId);

        Gate::authorize('update', $lesson);

        // تمرير المهمة الثقيلة للسيرفس
        $result = $this->chunkService->handleChunk(
            $request->upload_session_id,
            $request->chunk_index,
            $request->total_chunks,
            $request->file('file'),
            $lesson
        );

        // إذا أرجعت السيرفس كائن LessonContent، فهذا يعني أن التجميع تم بنجاح!
        if ($result) {
            return ApiResource::sendResponse("Video fully uploaded and merged. Processing started.", [
                'content' => $result
            ], 200);
        }

        // إذا لسه عم نرفع قطع
        return ApiResource::sendResponse("Chunk {$request->chunk_index} uploaded successfully.", [
            'next_chunk' => $request->chunk_index + 1
        ], 200);
    }
}
