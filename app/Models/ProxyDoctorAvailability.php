<?php

// app/Models/ProxyDoctorAvailability.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyDoctorAvailability extends Model
{
    protected $table = 'proxy_doctor_availabilities';
    protected $guarded = [];
    protected $casts = [
        'status'      => 'boolean',
        'start_time'  => 'datetime:H:i:s',
        'end_time'    => 'datetime:H:i:s',
    ];
protected $attributes = [
        'status'       => 1,
    ];
    public function schedule() { return $this->belongsTo(ProxyDoctorSchedule::class, 'schedule_id'); }

}

