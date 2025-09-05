<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusChangedNotification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
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
