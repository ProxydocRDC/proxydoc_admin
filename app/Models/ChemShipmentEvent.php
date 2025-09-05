<?php

namespace App\Models;

use App\Models\ChemShipment;
use Illuminate\Database\Eloquent\Model;

class ChemShipmentEvent extends Model
{
    protected $guarded = [];
    protected $table = "chem_shipments_events";
     protected $casts = [
        'location'  => 'array',     // <- important
        'event_time'=> 'datetime',
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];


   // Évènement (suivi) d'une livraison
public function shipment()
{
    return $this->belongsTo(ChemShipment::class, 'shipment_id');
}

}
