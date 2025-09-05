<?php
namespace App\Models;

use App\Models\User;
use App\Models\ChemProduct;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

class ChemCategory extends Model
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
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
    // Catégorie d'un produit pharmaceutique
    public function products()
    {
        return $this->hasMany(ChemProduct::class, 'category_id');
    }
    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

}
