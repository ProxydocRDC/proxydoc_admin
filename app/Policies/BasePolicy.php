<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Slug de la ressource/permission (ex: 'chem_shipment').
     * Règle générale des permissions générées par Shield :
     *  - view_any_{$slug}
     *  - view_{$slug}
     *  - create_{$slug}
     *  - update_{$slug}
     *  - delete_{$slug}
     *  - delete_any_{$slug}
     *  - force_delete_{$slug}
     *  - force_delete_any_{$slug}
     *  - restore_{$slug}
     *  - restore_any_{$slug}
     *  - replicate_{$slug}
     *  - reorder_{$slug}
     *  - export_{$slug}
     */
    protected string $slug;

    protected function can(User $user, string $perm): bool
    {
        return $user->can($perm);
    }

    public function viewAny(User $user): bool              { return $this->can($user, "view_any_{$this->slug}"); }
    public function view(User $user, Model $record): bool  { return $this->can($user, "view_{$this->slug}"); }
    public function create(User $user): bool               { return $this->can($user, "create_{$this->slug}"); }
    public function update(User $user, Model $record): bool{ return $this->can($user, "update_{$this->slug}"); }
    public function delete(User $user, Model $record): bool{ return $this->can($user, "delete_{$this->slug}"); }

    public function deleteAny(User $user): bool            { return $this->can($user, "delete_any_{$this->slug}"); }
    public function forceDelete(User $user, Model $r): bool{ return $this->can($user, "force_delete_{$this->slug}"); }
    public function forceDeleteAny(User $user): bool       { return $this->can($user, "force_delete_any_{$this->slug}"); }
    public function restore(User $user, Model $record): bool{ return $this->can($user, "restore_{$this->slug}"); }
    public function restoreAny(User $user): bool           { return $this->can($user, "restore_any_{$this->slug}"); }
    public function replicate(User $user, Model $record): bool{ return $this->can($user, "replicate_{$this->slug}"); }
    public function reorder(User $user): bool              { return $this->can($user, "reorder_{$this->slug}"); }
    public function export(User $user): bool               { return $this->can($user, "export_{$this->slug}"); }
}
