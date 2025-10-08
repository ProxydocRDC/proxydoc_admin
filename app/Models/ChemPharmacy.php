<?php
namespace App\Models;

use App\Models\ChemPharmacyProduct;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChemPharmacy extends Model
{
    use HasS3MediaUrls;
    protected $guarded    = [];
    protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status'     => 'integer',
        'rating'     => 'float',
        'nb_review'  => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessors pratiques :
    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image'); // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('images'); // foreach ($model->images_urls as $url) ...
    }
    // Pharmacie (lieu de vente de médicaments)
    public function products()
    {
        return $this->hasMany(ChemPharmacyProduct::class);
    }
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id'); // adapte le modèle/champ d’affichage
    }
    public function zone()
    {
        return $this->belongsTo(\App\Models\MainZone::class); // adapte le modèle/champ d’affichage
    }
    public function supplier()
    {
        return $this->belongsTo(\App\Models\ChemSupplier::class, 'supplier_id'); // adapte le modèle/champ d’affichage
    }
    public function orders()
    {return $this->hasMany(\App\Models\ChemOrder::class, 'pharmacy_id');}

    public function keyFromUrl(string $url): ?string
    {
        $parts  = parse_url($url);
        $path   = isset($parts['path']) ? ltrim($parts['path'], '/') : null; // ex: "pharmacies/pharma5.jpeg" ou "proxydocfiles/pharmacies/..."
        $bucket = config('filesystems.disks.s3.bucket');
        if ($bucket && Str::startsWith($path, $bucket . '/')) {
            $path = Str::after($path, $bucket . '/');
        }
        return $path ?: null;
    }
public function pharmacyProducts()
{
    return $this->hasMany(\App\Models\ChemPharmacyProduct::class, 'pharmacy_id');
}

}
