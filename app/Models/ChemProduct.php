<?php

namespace App\Models;

use App\Models\ChemCategory;
use App\Models\ChemPosology;
use App\Models\ChemManufacturer;
use App\Models\ChemPharmacyProduct;
use App\Models\ChemPharmaceuticalForm;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

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
