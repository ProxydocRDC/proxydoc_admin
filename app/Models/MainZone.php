<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MainZone extends Model
{
    use SoftDeletes;

    protected $table = 'main_zones';
    protected $guarded = [];  // ou remplis $fillable

    protected $casts = [
        'status'       => 'integer',
        'delivery_fee' => 'decimal:2',
        'distance'     => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'deleted_at'   => 'datetime',
    ];
  protected $attributes = [
        'status' => 1, // actif par défaut
    ];
    /** Relations utiles si tu as déjà ces modèles */
    public function countryRef()
    {
        // on suppose que main_countries a une colonne 'iso2'
        return $this->belongsTo(MainCountry::class, 'country', 'iso2');
    }

    public function cityRef()
    {
        // on stocke l'id de main_cities dans 'city' (varchar ici, OK)
        return $this->belongsTo(MainCity::class, 'city', 'id');
    }

    /** Accessors pratiques */
    public function getCountryNameAttribute(): ?string
    {
        return $this->countryRef?->name;
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->cityRef?->city;
    }
}
