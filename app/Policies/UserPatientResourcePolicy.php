<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPatientResourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user::patient');
    }

    public function view(User $user): bool
    {
        return $user->can('view_user::patient');
    }
}
