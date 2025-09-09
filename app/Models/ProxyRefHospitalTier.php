<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyRefHospitalTier extends Model
{
    protected $table = 'proxy_ref_hospital_tiers';
    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'rate'   => 'decimal:2',
    ];
}
