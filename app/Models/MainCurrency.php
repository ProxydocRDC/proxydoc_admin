<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainCurrency extends Model
{
   protected $guarded = [];
    protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
}
