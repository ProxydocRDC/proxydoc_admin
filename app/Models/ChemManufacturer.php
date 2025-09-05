<?php

namespace App\Models;

use App\Models\ChemProduct;
use Illuminate\Database\Eloquent\Model;

class ChemManufacturer extends Model
{
    protected $guarded = [];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
   // Producteur de médicaments
public function products()
{
    return $this->hasMany(ChemProduct::class, 'manufacturer_id');
}


}
