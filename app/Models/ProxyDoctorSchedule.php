<?php

// app/Models/ProxyDoctorSchedule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyDoctorSchedule extends Model
{
    protected $table = 'proxy_doctor_schedules';
    protected $guarded = [];
    protected $casts = [
        'status'     => 'boolean',
        'is_default' => 'boolean',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];
protected $attributes = [
        'status'       => 1,
    ];
    // utilisateur (si tu veux lier à users)
    // public function doctorUser() { return $this->belongsTo(User::class, 'doctor_user_id'); }

    // // médecin (si tu veux lier au profil médecin par user_id)
    // public function doctor() { return $this->belongsTo(ProxyDoctor::class, 'doctor_user_id', 'user_id'); }
public function doctorUser()
{
    return $this->belongsTo(\App\Models\User::class, 'doctor_user_id');
}

// (facultatif) accès au profil médecin via user_id
public function doctor()
{
    return $this->belongsTo(\App\Models\ProxyDoctor::class, 'doctor_user_id', 'user_id');
}

    public function availabilities() { return $this->hasMany(ProxyDoctorAvailability::class, 'schedule_id'); }
}
