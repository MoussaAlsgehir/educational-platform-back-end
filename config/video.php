<?php

return [
    'ffmpeg_path'  => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'ffprobe_path' => env('FFPROBE_PATH', '/usr/bin/ffprobe'),
    'timeout'      => env('VIDEO_PIPELINE_TIMEOUT', 3600),
    'cloud_disk'   => env('VIDEO_CLOUD_DISK', 's3'),

    'defaults' => [
        'crf'              => 23,
        'audio_codec'      => 'aac',
        'audio_bitrate'    => '128k',
        'audio_channels'   => 2,
        'audio_rate'       => 48000,
        'segment_duration' => 6,
    ]
];
