<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{

     public function creating(User $user): void
    {
        // Si rien n’est fourni, on force “patient”
        if (empty($user->default_role)) {
            $user->default_role = User::ROLE_PATIENT; // 5
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
         if ($user->isDirty('status')) {
            // Envoyer une notification à l'utilisateur
            // Notification::send($user, new StatusChangedNotification($user));
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
