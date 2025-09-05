<?php

namespace App\Models;

use App\Models\ChemOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ChemOrderPayment extends Model
{
    protected $table = 'chem_orders_payments';
    protected $guarded = [];
     protected $casts = [
        'total_amount' => 'decimal:3',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'status' => 'integer',
    ];
     protected $attributes = [
        'status' => 1, // actif par défaut
    ];
  public function pharmacy()
    {
        return $this->belongsTo(\App\Models\ChemPharmacy::class, 'pharmacy_id');
    }

    /** Scope pratique pour filtrer par fournisseur */
    public function scopeForSupplier(Builder $q, int $supplierId): Builder
    {
        return $q->whereHas('pharmacy', fn ($p) => $p->where('supplier_id', $supplierId));
    }

    // Paiement lié à une commande
public function order()
{
    return $this->belongsTo(ChemOrder::class, 'order_id');
}

}
