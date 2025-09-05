<?php

namespace App\Models\Models;

use App\Models\User;
use App\Models\Models\MainUser;
use App\Models\Models\MainPermission;
use Illuminate\Database\Eloquent\Model;

class MainRole extends Model
{
    protected $guarded = [];
   // Rôle (admin, client, docteur, etc.)
public function users()
{
    return $this->belongsToMany(User::class, 'main_users_roles', 'role_id', 'user_id');
}

public function permissions()
{
    return $this->belongsToMany(MainPermission::class, 'main_roles_permissions', 'role_id', 'permission_id');
}

}
