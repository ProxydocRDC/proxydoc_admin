<?php

use App\Support\Storage\S3Helpers;

if (! function_exists('s3_url')) {
    function s3_url(?string $path, bool $public = true, string $disk = 's3', int $ttl = 5): ?string
    {
        return S3Helpers::url($path, $public, $disk, $ttl);
    }
}
