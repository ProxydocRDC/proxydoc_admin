<?php

namespace App\Filament\Resources\ProxyDoctorResource\Pages;

use App\Models\User;
use App\Support\Sms;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProxyDoctorResource;

class CreateProxyDoctor extends CreateRecord
{
    protected static string $resource = ProxyDoctorResource::class;
    protected function afterCreate(): void
    {

        $this->record->user?->update([
        'default_role' => User::ROLE_DOCTOR, // 2
    ]);
        // $doctor = $this->record;        // ProxyDoctor
        // $to     = $doctor->user?->phone; // ou un champ phone du docteur si tu l’as
        // if ($to) {
        //     $ok = Sms::send($to, "Bonjour Dr {$doctor->fullname}, votre compte médecin a été validé chez ProxyDoc.");
        //     if (! $ok) {
        //         // Optionnel : afficher un toast Filament si échec
        //         \Filament\Notifications\Notification::make()
        //             ->title("SMS non envoyé")
        //             ->body("Impossible d’envoyer le SMS à {$to}.")
        //             ->danger()
        //             ->send();
        //     }
        // }
    }
}
