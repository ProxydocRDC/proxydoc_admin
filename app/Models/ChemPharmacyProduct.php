<?php
namespace App\Models;

use App\Models\ChemPharmacy;
use App\Models\ChemProduct;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChemPharmacyProduct extends Model
{
    use HasS3MediaUrls;
    protected $guarded = [];
    protected $casts   = [
        'expiry_date' => 'date',
        'cost_price'  => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'stock_qty'   => 'decimal:2',
        'status'      => 'integer',
    ];
    protected $attributes = [
        'status' => 1, // actif par défaut
    ];
    protected $table = 'chem_pharmacy_products';

    // Accessors pratiques :
    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image'); // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('images'); // foreach ($model->images_urls as $url) ...
    }
    // Produit pharmaceutique stocké dans une pharmacie
    public function pharmacy()
    {
        return $this->belongsTo(ChemPharmacy::class, 'pharmacy_id');
    }

    public function product()
    {
        return $this->belongsTo(ChemProduct::class, 'product_id');
    }
    public function manufacturer()
    {return $this->belongsTo(\App\Models\ChemManufacturer::class);}

    /** Convertit une URL S3 complète en clé, ou retourne la clé telle quelle */
    protected function s3KeyFromUrlOrKey(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        // déjà une clé ?
        if (! preg_match('#^https?://#i', $value)) {
            $key    = ltrim($value, '/');
            $bucket = config('filesystems.disks.s3.bucket');
            if ($bucket && str_starts_with($key, $bucket . '/')) {
                $key = substr($key, strlen($bucket) + 1);
            }
            return $key ?: null;
        }

        // URL → path
        $parts = parse_url($value);
        $path  = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
        if (! $path) {
            return null;
        }

        $bucket = config('filesystems.disks.s3.bucket');
        if ($bucket && str_starts_with($path, $bucket . '/')) {
            $path = substr($path, strlen($bucket) + 1);
        }
        return $path ?: null;
    }

    /** URL signée à afficher : image propre à la pharmacie sinon image du produit */
    public function displayImageUrl(int $ttlMinutes = 10): ?string
    {
        // 1) image propre (clé ou URL) ?
        if ($this->image) {
            $key = $this->s3KeyFromUrlOrKey($this->image);
            if ($key) {
                return Storage::disk('s3')->temporaryUrl($key, now()->addMinutes($ttlMinutes));
            }
        }

        // 2) fallback : première image du produit
        return $this->product?->firstSignedImageUrl($ttlMinutes);
    }

}
