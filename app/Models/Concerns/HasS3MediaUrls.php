<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use App\Support\Storage\S3Helpers;
use Illuminate\Support\Facades\Storage;

trait HasS3MediaUrls
{
    /** URL pour un champ string (ex: 'image') */
    public function mediaUrl(string $column, int $ttl = 5): ?string
    {
        $path = $this->{$column} ?? null;
        if (! $path) return null;

        // URL absolue déjà en base (CloudFront, etc.)
        if (Str::startsWith($path, ['http://','https://'])) {
            return $path;
        }

        try {
            $primaryDisk = $this->disk ?? config('filesystems.default', 's3');
            $isPublic = false;

            // Vérifier existence sans lever d'exception (catch en cas d'erreur S3/connectivité)
            $disk = $primaryDisk;
            try {
                if (! Storage::disk($primaryDisk)->exists($path)) {
                    $disk = Storage::disk('public')->exists($path) ? 'public' : $primaryDisk;
                }
            } catch (\Throwable $e) {
                // Fichier peut-être supprimé ou erreur S3 : on tente quand même l'URL
                $disk = $primaryDisk;
            }

            return $isPublic || $disk === 'public'
                ? Storage::disk($disk)->url($path)
                : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($ttl));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** URLs pour un champ array/json (ex: 'images') */
    public function mediaUrls(string $column, int $ttl = 5): array
    {
        $paths = $this->{$column} ?? [];
        if (is_string($paths)) {
            $decoded = json_decode($paths, true);
            if (json_last_error() === JSON_ERROR_NONE) $paths = $decoded;
        }
        if (! is_array($paths)) return [];

        $disk = $this->disk ?? config('filesystems.default', 's3');
        $pub  = ($this->is_public ?? true) === true;

        return array_values(array_filter(array_map(
            fn ($p) => S3Helpers::url($p, $pub, $disk, $ttl),
            $paths
        )));
    }
}
