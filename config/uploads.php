<?php

$releaseMaxMegabytes = (int) env('RELEASE_UPLOAD_MAX_MB', 512);

return [
    'release_max_mb' => $releaseMaxMegabytes,
    'release_max_kb' => $releaseMaxMegabytes * 1024,
    'temporary_upload_minutes' => (int) env('RELEASE_UPLOAD_TIME_MINUTES', 30),
];
