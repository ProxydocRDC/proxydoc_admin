<?php

namespace App\Models;

use App\Models\ProxyDoctor;
use App\Models\ProxyService;
use Illuminate\Database\Eloquent\Model;

class ProxyDoctorService extends Model
{
  protected $attributes = [
        'status'       => 1,
    ];

     protected $table = 'proxy_doctor_services';
    protected $guarded = [];
    protected $casts = ['status'=>'boolean'];


      // Appartient à un ProxyDoctor, via doctor_user_id -> proxy_doctors.user_id
    public function doctor()
    {
        return $this->belongsTo(ProxyDoctor::class, 'doctor_user_id', 'user_id');
    }

    // (utile si tu veux accéder directement à l'utilisateur)
    public function doctorUser()
    {
        return $this->belongsTo(User::class, 'doctor_user_id', 'id');
    }

    public function service()
    {
        return $this->belongsTo(ProxyService::class, 'service_id');
    }
}
