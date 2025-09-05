<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BaseNamedPermissionPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
      use HandlesAuthorization;

    /**
     * Le nom EXACT de la permission dans la BD (ex: 'page_ShipmentsTracker').
     */
    protected string $viewPermission;

    /**
     * (Optionnel) Bypass pour Super Admin (Shield)
     */
    public function before(User $user, string $ability): bool|null
    {
        $super = config('filament-shield.super_admin.name', 'Super Admin');
        return $user->hasRole($super) ? true : null;
    }

    public function view(User $user): bool
    {
        return $user->can($this->viewPermission);
    }
}
