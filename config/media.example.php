<?php

declare(strict_types=1);

/**
 * Copy to config/media.php and configure for the consuming application.
 * Never commit config/media.php or real credentials.
 */
return [
    'storage' => [
        'root' => getenv('MEDIA_STORAGE_PATH') ?: dirname(__DIR__, 2) . '/private-media-storage',
    ],
    'upload' => [
        'max_file_size' => (int) (getenv('MEDIA_MAX_FILE_SIZE') ?: 10 * 1024 * 1024),
        'max_files_per_request' => (int) (getenv('MEDIA_MAX_FILES_PER_REQUEST') ?: 10),
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_width' => 10000,
        'max_height' => 10000,
        'max_pixels' => 40000000,
    ],
    'processing' => [
        'driver' => 'gd',
        'variants' => [
            'thumbnail' => ['width' => 320, 'height' => 320, 'fit' => 'contain'],
            'medium' => ['width' => 900, 'height' => 900, 'fit' => 'contain'],
            'large' => ['width' => 1600, 'height' => 1600, 'fit' => 'contain'],
        ],
        'jpeg_quality' => 85,
        'png_compression' => 6,
        'webp_quality' => 82,
    ],
];
