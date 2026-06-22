<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
