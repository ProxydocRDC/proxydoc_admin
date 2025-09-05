<?php

namespace App\Support\Storage;

use Illuminate\Support\Facades\Storage;

class S3Helpers
{
    /** URL pour une image S3 (public => url, privé => temporaryUrl) */
    public static function url(?string $path, bool $public = true, string $disk = 's3', int $ttlMinutes = 5): ?string
    {
        if (! $path) return null;

        // Si déjà une URL absolue (CloudFront, etc.)
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $public
            ? Storage::disk($disk)->url($path)
            : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($ttlMinutes));
    }
}
