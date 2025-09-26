<?php

namespace App\Models;

use App\Models\ChemCategory;
use App\Models\ChemPosology;
use App\Models\ChemManufacturer;
use App\Models\ChemPharmacyProduct;
use App\Models\ChemPharmaceuticalForm;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChemProduct extends Model
{
       use HasS3MediaUrls;

    // Accessors pratiques :
    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image');       // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('images');     // foreach ($model->images_urls as $url) ...
    }
     /** Retourne toutes les URLs (tableau), en filtrant les vides */
    public function imageUrls(): array
    {
        $arr = $this->image ?? [];       // ou $this->images
        if (! is_array($arr)) {
            $arr = $arr ? [$arr] : [];
        }
        return array_values(array_filter($arr, fn ($u) => is_string($u) && $u !== ''));
    }

    /** Retourne la 1re URL (ou null) */
    public function firstImageUrl(): ?string
    {
        $urls = $this->imageUrls();
        return $urls[0] ?? null;
    }

     protected function keyFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if (! isset($parts['path'])) return null;
        $path = ltrim($parts['path'], '/'); // ex: 'products/doliprane.jpeg'

        // Si jamais l’URL est en mode path-style et contient le bucket dans le path:
        $bucket = config('filesystems.disks.s3.bucket');
        if (str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path ?: null;
    }

  /**
     * Retourne la clé S3 (ex: "products/file.jpg") à partir d'une URL S3 complète
     * ou retourne telle quelle si on reçoit déjà une clé.
     */
    protected function s3KeyFromUrlOrKey(?string $value): ?string
    {
        if (!is_string($value) || $value === '') return null;

        // Déjà une clé? (pas d'http/https)
        if (!preg_match('#^https?://#i', $value)) {
            $key = ltrim($value, '/');
            // enlève éventuellement "bucket/" si présent
            $bucket = config('filesystems.disks.s3.bucket');
            if ($bucket && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
            return $key ?: null;
        }

        // URL complète → extraire le path
        $parts = parse_url($value);
        $path  = isset($parts['path']) ? ltrim($parts['path'], '/') : null; // "products/file.jpg" ou "bucket/products/file.jpg"
        if (!$path) return null;

        $bucket = config('filesystems.disks.s3.bucket');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path ?: null;
    }

    /** Retourne la liste des clés S3 (filtrées) à partir de la colonne JSON "images". */
    public function imageKeys(): array
    {
        $raw = $this->images ?? [];
        if (!is_array($raw)) $raw = $raw ? [$raw] : [];
        $keys = [];
        foreach ($raw as $item) {
            $key = $this->s3KeyFromUrlOrKey(is_string($item) ? $item : null);
            if ($key) $keys[] = $key;
        }
        return $keys;
    }

    /** URLs signées (TTL en minutes) pour toutes les images. */
    public function signedImageUrls(int $ttlMinutes = 10): array
    {
        $exp = now()->addMinutes($ttlMinutes);
        return array_values(array_filter(array_map(
            fn ($key) => Storage::disk('s3')->temporaryUrl($key, $exp),
            $this->imageKeys()
        )));
    }

    /** Première URL signée (ou null). */
    public function firstSignedImageUrl(int $ttlMinutes = 10): ?string
    {
        $all = $this->signedImageUrls($ttlMinutes);
        return $all[0] ?? null;
        // return $all[0] ?? null;
    }

    protected $guarded = [];
    protected $casts = [
        "images"=>"array",
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];

   // Produit pharmaceutique
public function category()
{
    return $this->belongsTo(ChemCategory::class, 'category_id');
}

public function manufacturer()
{
    return $this->belongsTo(ChemManufacturer::class, 'manufacturer_id');
}

public function form()
{
    return $this->belongsTo(ChemPharmaceuticalForm::class, 'form_id');
}

public function posologies()
{
    return $this->hasMany(ChemPosology::class, 'product_id');
}

public function pharmacyProducts()
{
    return $this->hasMany(ChemPharmacyProduct::class, 'product_id');
}

}
