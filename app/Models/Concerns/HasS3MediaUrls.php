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

        // Disque principal (colonne `disk` si tu l'as, sinon default)
        $primaryDisk = $this->disk ?? config('filesystems.default', 's3');

        // Public/privé (colonne is_public si présente, sinon on suppose public)
        $isPublic = false;
        // $isPublic = property_exists($this, 'is_public') ? (bool) $this->is_public : true;

        // Choix du disque effectif (fallback sur 'public' si le fichier n'existe pas sur le principal)
        $disk = Storage::disk($primaryDisk)->exists($path)
            ? $primaryDisk
            : (Storage::disk('public')->exists($path) ? 'public' : $primaryDisk);

        return $isPublic || $disk === 'public'
            ? Storage::disk($disk)->url($path)
            : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($ttl));
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
