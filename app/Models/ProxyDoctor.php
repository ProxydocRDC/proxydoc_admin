<?php
namespace App\Models;

use App\Models\ProxyDoctorSchedule;
use App\Models\ProxyService;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ProxyDoctor extends Model
{
    protected $table   = 'proxy_doctors';
    protected $guarded = [];
    protected $casts   = [
        'status'         => 'boolean',
        'expertise_skills' => 'array',
        // 'languages_spoken'=> 'array',   // SET → array (si tu préfères string, retire ce cast)
        'career_history' => 'array',
        'rating'         => 'float',
    ];
    protected $attributes = [
        'status' => 1,
    ];
    protected function languagesSpoken(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? explode(',', $value) : [],
            set: fn($value) => is_array($value) ? implode(',', $value) : (string) $value,
        );
    }
    public function user()
    {return $this->belongsTo(User::class, 'user_id');}
    public function schedule()
    {return $this->hasMany(ProxyDoctorSchedule::class);}
    // Many-to-many vers les services (via pivot) — attention aux clés custom
    public function services()
    {
        return $this->belongsToMany(
            ProxyService::class,
            'proxy_doctor_services',
            'doctor_user_id', // FK du parent (ProxyDoctor) dans le pivot
            'service_id',     // FK du related (ProxyService) dans le pivot
            'user_id',        // clé locale sur ProxyDoctor
            'id'              // clé locale sur ProxyService
        )->withPivot(['status', 'created_by', 'updated_by', 'created_at', 'updated_at']);
    }
    // app/Models/ProxyDoctor.php

    public function primaryHospital()
    {
        return $this->belongsTo(\App\Models\ChemHospital::class, 'primary_hospital_id');
    }

    public function academicTitle()
    {
        return $this->belongsTo(\App\Models\ProxyRefAcademicTitle::class, 'academic_title_id');
    }

}
