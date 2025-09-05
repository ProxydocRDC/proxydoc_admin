<?php

namespace App\Models;

use App\Models\ChemProduct;
use Illuminate\Database\Eloquent\Model;

class ChemPosology extends Model
{
    protected $guarded = [];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
  // Posologie (dosage) liée à un produit
public function product()
{
    return $this->belongsTo(ChemProduct::class, 'product_id');
}

}
