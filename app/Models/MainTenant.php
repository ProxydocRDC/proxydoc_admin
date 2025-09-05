<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MainTenant extends Model
{
   public function users()
{
    return $this->hasMany(User::class);
}

}
