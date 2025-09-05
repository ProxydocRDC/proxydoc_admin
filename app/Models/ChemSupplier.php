<?php

namespace App\Models;

use App\Models\User;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

class ChemSupplier extends Model
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
  public function user()
    {
        // Remplace User::class par ton vrai modèle utilisateur (ex: MainUser::class)
        return $this->belongsTo(User::class, 'user_id');
    }
  public function pharmacie()
    {
        // Remplace User::class par ton vrai modèle utilisateur (ex: MainUser::class)
        return $this->hasMany(ChemPharmacy::class,'supplier_id');
    }
    // si tu utilises aussi une zone dans ce formulaire/table
    // public function zone()
    // {
    //     return $this->belongsTo(Zone::class, 'zone_id');
    // }

}
