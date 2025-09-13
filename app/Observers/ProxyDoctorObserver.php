<?php

// app/Observers/ProxyDoctorObserver.php
namespace App\Observers;

use App\Models\ProxyDoctor;
use App\Models\User;

class ProxyDoctorObserver
{
    public function created(ProxyDoctor $doctor): void
    {
        $doctor->user?->update(['default_role' => User::ROLE_DOCTOR]);
    }

    // (facultatif) si on supprime le lien médecin et qu’il n’en reste plus,
    // on le redescend en patient
    public function deleted(ProxyDoctor $doctor): void
    {
        if (! $doctor->user) return;

        $stillDoctor = ProxyDoctor::where('user_id', $doctor->user_id)->exists();
        if (! $stillDoctor) {
            $doctor->user->update(['default_role' => User::ROLE_PATIENT]);
        }
    }
}
