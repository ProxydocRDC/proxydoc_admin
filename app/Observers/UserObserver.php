<?php

namespace App\Observers;

use App\Models\User;
use App\Models\ProxyPatient;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusChangedNotification;

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
     * Handle the User "created" event.
     */
     public function created(User $user): void
    {
        // Créer la fiche patient si inexistante
        ProxyPatient::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status'     => 1,
                'created_by' => $user->id,          // ou Auth::id() si en back
                'fullname'   => trim(($user->firstname ?? '').' '.($user->lastname ?? '')),
                'birthdate'  => now(),
                'gender'     => 'other',
                'phone'      => $user->phone ?? null,
                'email'      => $user->email ?? null,
                'relation'   => 'self',
            ],
        );
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
