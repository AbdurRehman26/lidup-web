<?php

$releaseMaxMegabytes = (int) env('RELEASE_UPLOAD_MAX_MB', 512);

return [
    'release_max_mb' => $releaseMaxMegabytes,
    'release_max_kb' => $releaseMaxMegabytes * 1024,
    'temporary_upload_minutes' => (int) env('RELEASE_UPLOAD_TIME_MINUTES', 30),
    'release_extensions' => ['dmg', 'pkg', 'zip'],
    'release_mime_types' => [
        'application/x-apple-diskimage',
        'application/vnd.apple.disk-image',
        'application/x-diskcopy',
        'application/x-iso9660-image',
        'application/zlib',
        'application/x-zlib',
        'application/octet-stream',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-xar',
    ],
];
