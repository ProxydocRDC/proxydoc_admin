<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
class proxy_categories extends Model
{
    /** @use HasFactory<\Database\Factories\ProxyCategoriesFactory> */
    use HasFactory;
     use SoftDeletes;

    protected $table = 'proxy_categories';
    protected $guarded = [];

    protected $casts = [
        'status' => 'integer',
    ];
protected $attributes = [
        'status'       => 1,
    ];
    // Si ta colonne commission reste en VARCHAR mais contient un nombre,
    // on la normalise en float à la lecture, et on stocke "12.50" à l'écriture.
    protected function commission(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : (float) str_replace(',', '.', $v),
            set: fn ($v) => $v === null ? null : number_format((float) $v, 2, '.', ''),
        );
    }

    public function scopeActive($q) { return $q->where('status', 1); }
}
