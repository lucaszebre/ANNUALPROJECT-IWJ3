<?php

return [

    /*
    | Largest video a teacher can upload. The file goes straight to MinIO,
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

];
