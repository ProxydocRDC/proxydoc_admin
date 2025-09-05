<?php

namespace App\Models;

use App\Models\ChemProduct;
use Illuminate\Database\Eloquent\Model;
use App\Models\ChemPharmacyProduct;

class ChemOrderItem extends Model
{
    protected $guarded = [];
    protected $table = 'chem_orders_items';
    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_price' => 'decimal:2',
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];


    // Produit dans une commande
public function order()
{
    return $this->belongsTo(ChemOrder::class, 'order_id');
}

public function product()
{
    return $this->belongsTo(ChemProduct::class, 'product_id');
}

public function pharmacyProduct()
{
    return $this->belongsTo(ChemPharmacyProduct::class, 'pharmacy_product_id');
}

}
