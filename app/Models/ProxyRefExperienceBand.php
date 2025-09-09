<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyRefExperienceBand extends Model
{
    protected $table = 'proxy_ref_experience_bands';
    protected $guarded = [];

    protected $casts = [
        'status'    => 'integer',
        'min_years' => 'integer',
        'max_years' => 'integer',
        'rate'      => 'decimal:2',
    ];
}
