<?php

namespace App\Models;

use App\Models\Models\MainRole;
use Illuminate\Database\Eloquent\Model;

class MainPermission extends Model
{
    protected $guarded = [];
  // Droit d’accès
public function roles()
{
    return $this->belongsToMany(MainRole::class, 'main_roles_permissions', 'permission_id', 'role_id');
}

}
