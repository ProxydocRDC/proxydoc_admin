<?php

namespace App\Models;

use App\Models\ProxyDoctor;
use App\Models\ProxyDoctorService;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Model;

class ProxyService extends Model
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
     protected $table = 'proxy_services';
    protected $guarded = [];
    protected $attributes = [
        'status'       => 1,
    ];
    protected $casts = ['status'=>'boolean'];
    public function doctorServices()
    {
        return $this->hasMany(ProxyDoctorService::class, 'service_id', 'id');
    }

    public function doctors()
    {
        return $this->belongsToMany(
            ProxyDoctor::class,
            'proxy_doctor_services',
            'service_id',       // FK du parent (ProxyService) dans le pivot
            'doctor_user_id',   // FK du related (ProxyDoctor via user_id) dans le pivot
            'id',               // clé locale sur ProxyService
            'user_id'           // clé locale sur ProxyDoctor
        )->withPivot(['status','created_by','updated_by','created_at','updated_at']);
    }
public function appointments()   { return $this->hasMany(\App\Models\ProxyAppointment::class, 'service_id'); }

}
