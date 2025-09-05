<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainStatus extends Model
{
   protected $guarded = [];


    protected $casts = [
        'status' => 'integer',
    ];
   protected $table = 'main_status'; // Specify the table name if it differs from the default pluralized form
     public $timestamps = true; // created_at / updated_at gérés par la BDD mais ok côté Eloquent

}
