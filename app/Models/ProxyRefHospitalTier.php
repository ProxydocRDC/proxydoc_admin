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
    public function hospitals()
{
    // hasMany vers la clé texte
    return $this->hasMany(\App\Models\ChemHospital::class, 'tier_code', 'code');
}

}
