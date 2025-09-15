<?php
namespace App\Models;

use App\Models\ChemPharmacyProduct;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

class ChemPharmacy extends Model
{
    use HasS3MediaUrls;
    protected $guarded    = [];
    protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',

    ];
  // Accessors pratiques :
    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image');       // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('images');     // foreach ($model->images_urls as $url) ...
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
}
