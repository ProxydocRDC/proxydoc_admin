<?php

namespace App\Models;

use App\Models\User;
use App\Models\ChemProduct;
use App\Models\ChemSupplier;
use App\Models\ProxyService;
use App\Models\Concerns\GeneratesCode;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;


class ChemHospital extends Model {
    use SoftDeletes,GeneratesCode,HasS3MediaUrls;


    // Optionnel : override

    // Personnalisation
    public function getCodePrefix(): string { return 'HOSP'; }   // => HOSP-ABC-12345
    public function getCodeColumn(): string { return 'code'; }   // colonne cible
    protected $guarded = [];
    protected $casts = [
        'departments' => 'array',
        'accepted_insurances' => 'array',
        'opening_hours' => 'array',
        'images' => 'array',
        'has_emergency' => 'boolean',
        'ambulance_available' => 'boolean',
        'allow_pricing' => 'boolean',
    ];
  protected $attributes = [
        'status' => 1, // actif par défaut
    ];
    public function supplier()   { return $this->belongsTo(ChemSupplier::class); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()    { return $this->belongsTo(User::class, 'updated_by'); }
    public function services()   { return $this->belongsToMany(ProxyService::class, 'chem_hospital_services', 'hospital_id', 'service_id'); }

     // Optionnel: URL de téléchargement (public/s3)
    // public function getPricingUrlAttribute(): ?string
    // {
    //     if (! $this->pricing_file) return null;
    //     return Storage::disk(config('filesystems.default', 'public'))->url($this->pricing_file);
    // }
 public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('image');       // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('images');     // foreach ($model->images_urls as $url) ...
    }
    public function tier()
{
    // belongsTo vers le code, pas l’ID
    return $this->belongsTo(\App\Models\ProxyRefHospitalTier::class, 'tier_code', 'code');
}

}
