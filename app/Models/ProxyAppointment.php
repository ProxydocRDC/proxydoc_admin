<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyAppointment extends Model
{
    protected $table = 'proxy_appointments';
    protected $guarded = [];
    protected $casts = [
        'status'      => 'boolean',
    ];
    protected $attributes = [
        'status' => 1,
    ];

    public function patient()
    {
        return $this->belongsTo(\App\Models\ProxyPatient::class, 'patient_id');
    }

    public function doctorUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_user_id');
    }

    public function service()
    {
        return $this->belongsTo(\App\Models\ProxyService::class, 'service_id');
    }

    public function schedule()
    {
        return $this->belongsTo(\App\Models\ProxyDoctorSchedule::class, 'schedule_id');
    }
}
