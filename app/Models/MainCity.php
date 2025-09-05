<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainCity extends Model
{
 protected $guarded = [];
  protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
 // Ville
public function country()
{
    return $this->belongsTo(MainCountry::class, 'country_id');
}

}
