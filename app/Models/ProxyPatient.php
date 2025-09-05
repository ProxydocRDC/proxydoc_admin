<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProxyPatient extends Model
{
  protected $attributes = [
        'status'       => 1,
    ];
    protected $table = 'proxy_patients';
    protected $guarded = [];
    protected $casts = [
        'status'=>'boolean',
        'birthdate'=>'date',
        'allergies'=>'array',
        'chronic_conditions'=>'array',
    ];
     // 🔗 user parent (compte auquel le patient est rattaché)
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
