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
        'status'       => 1,
    ];
  public function service() { return $this->belongsTo(\App\Models\ProxyService::class, 'service_id'); }

}
