<?php

namespace App\Models;

use App\Models\ChemProduct;
use App\Models\ChemPharmacy;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

class ChemPharmacyProduct extends Model
{
      use HasS3MediaUrls;
    protected $guarded = [];
    protected $casts = [
        'expiry_date' => 'date',
        'cost_price'  => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'stock_qty'   => 'decimal:2',
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
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
   // Produit pharmaceutique stocké dans une pharmacie
public function pharmacy()
{
    return $this->belongsTo(ChemPharmacy::class, 'pharmacy_id');
}

public function product()
{
    return $this->belongsTo(ChemProduct::class, 'product_id');
}

}
