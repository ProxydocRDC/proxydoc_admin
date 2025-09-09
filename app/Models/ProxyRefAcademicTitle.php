<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyRefAcademicTitle extends Model
{
    protected $table = 'proxy_ref_academic_titles';
    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
        'rate'   => 'decimal:2',
    ];
}
