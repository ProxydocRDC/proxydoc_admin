<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MainUserAddress extends Model
{
    protected $guarded = [];
 protected $attributes = [
        'status' => 1, // actif par défaut
    ];

    protected $casts = [
        'status' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
