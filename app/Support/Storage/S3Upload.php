<?php

namespace App\Support\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Upload
{
    /**
     * Envoie un fichier vers S3 et retourne infos utiles.
     * @param UploadedFile|string $file  UploadedFile (form/Filament) OU chemin local
     * @param string $dir                Dossier dans le bucket (ex: 'hospitals/pricings')
     * @param bool $public               true => public (Storage::url), false => privé
     * @param string|null $filename      Nom imposé (sinon UUID.ext)
     * @param string $disk               Disque (défaut 's3')
     * @return array{path:string,url:?string,disk:string,visibility:string,original_name:?string,size:?int,mime:?string}
     */
    public static function put(UploadedFile|string $file, string $dir, bool $public = true, ?string $filename = null, string $disk = 's3'): array
    {
        if ($file instanceof UploadedFile) {
            $ext      = $file->getClientOriginalExtension() ?: $file->extension();
            $original = $file->getClientOriginalName();
            $size     = $file->getSize();
            $mime     = $file->getClientMimeType();
        } else {
            $ext      = pathinfo($file, PATHINFO_EXTENSION);
            $original = basename($file);
            $size     = is_file($file) ? filesize($file) : null;
            $mime     = is_file($file) ? mime_content_type($file) : null;
        }

        $filename = $filename ?: (Str::uuid()->toString() . ($ext ? '.'.$ext : ''));
        $options  = ['visibility' => $public ? 'public' : 'private', 'CacheControl' => 'public, max-age=31536000'];

        if ($file instanceof UploadedFile) {
            $path = Storage::disk($disk)->putFileAs($dir, $file, $filename, $options);
        } else {
            $path = trim($dir, '/').'/'.$filename;
            Storage::disk($disk)->put($path, file_get_contents($file), $options);
        }

        return [
            'path'        => $path,
            'url'         => $public ? Storage::disk($disk)->url($path) : null,
            'disk'        => $disk,
            'visibility'  => $public ? 'public' : 'private',
            'original_name' => $original,
            'size'        => $size,
            'mime'        => $mime,
        ];
    }

    /** URL temporaire (pour objets privés) */
    public static function tempUrl(string $path, int $minutes = 10, string $disk = 's3'): string
    {
        return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /** Supprimer un objet S3 (sécurisé) */
    public static function delete(?string $path, string $disk = 's3'): void
    {
        if ($path) Storage::disk($disk)->delete($path);
    }
}
