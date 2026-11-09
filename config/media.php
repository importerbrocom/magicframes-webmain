<?php

return [
    // Maximum upload sizes in kilobytes. NOTE: PHP's upload_max_filesize / post_max_size
    // (and nginx client_max_body_size) must be >= these values. See README.
    'max_image_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 20480),
    'max_video_kb' => (int) env('MEDIA_MAX_VIDEO_KB', 512000),
];
