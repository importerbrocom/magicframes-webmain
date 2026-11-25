<?php

/*
 | Only the settings we change. Livewire's default limit for uploads is 12 MB, which would
 | silently block the larger photo / video limits set in config/media.php, so the limit is
 | raised to the biggest allowed file (the admin forms apply their own per-field limits).
 | PHP's upload_max_filesize / post_max_size on the host must be at least as large.
 */
return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:'.max(
            (int) env('MEDIA_MAX_VIDEO_KB', 512000),
            (int) env('MEDIA_MAX_HERO_VIDEO_KB', 102400),
            (int) env('MEDIA_MAX_IMAGE_KB', 20480),
        )],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 30,
        'cleanup' => true,
    ],
];
