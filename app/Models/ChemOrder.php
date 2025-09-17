<?php
namespace App\Models;

use App\Models\ChemOrderItem;
use App\Models\ChemOrderPayment;
use App\Models\ChemShipment;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ChemOrder extends Model
{
    use HasS3MediaUrls;
    protected $guarded    = [];
    protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'prescription'   => 'array', // <— text en base, manipulé en array côté PHP
        'total_amount'   => 'decimal:2',
        'amount'         => 'decimal:2',
        'tax'            => 'decimal:2',
        'delivery_costs' => 'decimal:2',
        'status'         => 'integer',
    ];
    // Commande de produits pharmaceutiques
    public function items()
    {
        return $this->hasMany(ChemOrderItem::class, 'order_id');
    }
    // public function customer()
    // {return $this->belongsTo(\App\Models\User::class, 'customer_id');}
    // public function pharmacy()
    // {
    //     return $this->belongsTo(\App\Models\ChemPharmacy::class, 'pharmacy_id');
    // }
    public function pharmacie()
    {
        return $this->belongsTo(\App\Models\ChemPharmacy::class, 'pharmacy_id');
    }

// alias pratique pour Filament (utilise le même FK)
    public function pharmacy()
    {
        return $this->pharmacie();
    }

// si besoin pour l'affichage "client"
    public function customer()
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    // (facultatif) garde un alias si tu veux :
    // public function pharmacie()
    // {
    //     return $this->pharmacy();
    // }

    /** Scope pour filtrer par fournisseur */
    public function scopeForSupplier(Builder $q, int $supplierId): Builder
    {
        return $q->whereHas('pharmacy', fn($p) => $p->where('supplier_id', $supplierId));
    }

    public function payments()
    {
        return $this->hasMany(ChemOrderPayment::class, 'order_id');
    }
    public function livraison()
    {
        return $this->hasMany(ChemShipment::class);
    }
    // total calculé (sum(qty * unit_price))
    public function getItemsTotalAttribute()
    {
        return $this->items->sum(fn($i) => (float) $i->qty * (float) $i->unit_price);
    }

    public function getPaidTotalAttribute()
    {
        return $this->payments->sum('amount');
    }

    public function getBalanceAttribute()
    {return $this->items_total - $this->paid_total;}

    public function supplier()
    { // accès rapide au fournisseur via la pharmacie
        return $this->hasOneThrough(
            \App\Models\ChemSupplier::class,
            \App\Models\ChemPharmacy::class,
            'id',          // local key on pharmacies
            'id',          // local key on suppliers
            'pharmacy_id', // foreign key on orders
            'supplier_id'  // foreign key on pharmacies
        );
    }
    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('prescription'); // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('prescriptions'); // foreach ($model->images_urls as $url) ...
    }
    public function latestShipment()
{
    return $this->hasOne(\App\Models\ChemShipment::class, 'order_id')
                ->latest('id');
}
public function deliveryPerson()
{
    return $this->belongsTo(\App\Models\User::class, 'delivery_person_id'); // sur ChemShipment
}
   
}
