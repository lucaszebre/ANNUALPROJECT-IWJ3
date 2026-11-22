<?php

return [

    /*
    | Largest video a teacher can upload. The file goes straight to the S3 storage (Garage),
    | so this limit does not depend on PHP's upload_max_filesize.
    */
    'max_upload_bytes' => (int) env('VIDEO_MAX_UPLOAD_MB', 2048) * 1024 * 1024,

    /*
    | Accepted video formats: MIME type => file extension.
    */
    'mime_types' => [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-matroska' => 'mkv',
    ],

    /*
    | How long the browser has to start the upload with its signed URL.
    */
    'upload_url_ttl_minutes' => 60,

    /*
    | HLS renditions made by the transcoding job, smallest first. A rendition taller than
    | the source is skipped, except the smallest one, so every video gets at least one.
    | Bitrates are in kbit/s.
    */
    'renditions' => [
        '360p' => ['height' => 360, 'video_bitrate' => 800],
        '720p' => ['height' => 720, 'video_bitrate' => 2800],
    ],

    'audio_bitrate' => 128,

    /*
    | Length of one HLS segment, in seconds.
    */
    'segment_seconds' => 6,

    /*
    | Longest ffmpeg run. Must stay below the timeout of the TranscodeVideo job.
    */
    'ffmpeg_timeout_seconds' => 3300,

    /*
    | How long the signed segment and thumbnail URLs given to the player stay valid.
    | Longer than a video plus its pauses: the player must reload the playlist after that.
    */
    'playback_url_ttl_minutes' => 360,

];
