<?php

return [
    // Maximum upload sizes in kilobytes. NOTE: PHP's upload_max_filesize / post_max_size
    // (and nginx client_max_body_size) must be >= these values. See README.
    'max_image_kb' => (int) env('MEDIA_MAX_IMAGE_KB', 20480),
    // Hero slide background video (kept small so the home page loads fast).
    'max_hero_video_kb' => (int) env('MEDIA_MAX_HERO_VIDEO_KB', 51200),
    'max_video_kb' => (int) env('MEDIA_MAX_VIDEO_KB', 512000),

    // Upper bound (MB) PHP may use while resizing one image. Bigger images keep the original only.
    'max_processing_memory_mb' => (int) env('MEDIA_MAX_PROCESSING_MEMORY_MB', 1024),
];
