<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $lesson_id
 * @property string $upload_session_id
 * @property int $chunk_index
 * @property int $total_chunks
 * @property string $temporary_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereChunkIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereLessonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereTemporaryPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereTotalChunks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VideoChunk whereUploadSessionId($value)
 * @mixin \Eloquent
 */
class VideoChunk extends Model
{
    protected $table = 'video_chunks';
    protected $fillable = [
        'lesson_id',
        'upload_session_id',
        'chunk_index',
        'total_chunks',
        'temporary_path',
    ];
}
