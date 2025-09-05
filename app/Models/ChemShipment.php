<?php

namespace App\Models;

use App\Models\ChemOrder;
use Illuminate\Database\Eloquent\Model;
use App\Models\ChemShipmentEvent;

class ChemShipment extends Model
{
  protected $guarded = [];
  // Livraison liée à une commande



public function events()
{
    return $this->hasMany(ChemShipmentEvent::class,'shipment_id');
}

 protected $casts = [
        'shipped_at'        => 'datetime',
        'delivered_at'      => 'datetime',
        'estimated_delivery'=> 'integer',
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    /** Relations: adapte les classes si tu utilises d’autres modèles */
    // Livraison liée à une commande
    public function order()           { return $this->belongsTo(\App\Models\ChemOrder::class, 'order_id'); }
    public function deliveryPerson()  { return $this->belongsTo(\App\Models\User::class, 'delivery_person_id'); }
    public function customer()        { return $this->belongsTo(\App\Models\User::class, 'customer_id'); }
    public function address()         { return $this->belongsTo(\App\Models\MainUserAddress::class, 'address_id'); }


}
